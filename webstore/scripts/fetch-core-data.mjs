import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { resolve } from 'node:path'
import { loadEnv } from 'vite'

const env = { ...loadEnv('', process.cwd(), ''), ...process.env }
const endpoint = env.CATALOGUE_API_URL?.trim()
const username = env.CATALOGUE_BASIC_AUTH_USERNAME?.trim()
const password = env.CATALOGUE_BASIC_AUTH_PASSWORD
const localPath = env.DISTRICT_CORE_DATA_LOCAL_PATH?.trim()

if (!localPath && (!endpoint || !username || !password)) {
  throw new Error('DistrictCoreData build variables are missing. Configure CATALOGUE_API_URL and its Basic Auth credentials.')
}

const authorization = `Basic ${Buffer.from(`${username}:${password}`).toString('base64')}`
const datasetBaseUrl = endpoint ? new URL(endpoint.endsWith('/') ? endpoint : `${endpoint}/`) : null

async function fetchDataset(filename) {
  if (localPath) return JSON.parse(await readFile(resolve(localPath, filename), 'utf8'))

  const response = await fetch(new URL(filename, datasetBaseUrl), {
    headers: { Authorization: authorization, Accept: 'application/json' },
  })

  if (!response.ok) throw new Error(`DistrictCoreData ${filename} request failed with status ${response.status}.`)
  return response.json()
}

function validateGroups(value) {
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
  if (!Array.isArray(value) || value.length === 0 || value.some((group) => (
    typeof group?.id !== 'string'
    || !uuid.test(group.id)
    || typeof group?.group_name !== 'string'
    || !Number.isInteger(group?.sort_order)
  ))) throw new Error('DistrictCoreData groups.json does not match the expected schema.')
}

function validateSections(value) {
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
  if (!Array.isArray(value) || value.length === 0 || value.some((section) => (
    typeof section?.id !== 'string'
    || !uuid.test(section.id)
    || typeof section?.group_id !== 'string'
    || !uuid.test(section.group_id)
    || typeof section?.group !== 'string'
    || typeof section?.section_type !== 'string'
    || !Number.isInteger(section?.section_id)
    || typeof section?.section_name !== 'string'
  ))) throw new Error('DistrictCoreData sections.json does not match the expected schema.')
}

const [groups, sections] = await Promise.all([
  fetchDataset('groups.json'),
  fetchDataset('sections.json'),
])

validateGroups(groups)
validateSections(sections)

const groupIds = new Set(groups.map((group) => group.id))
if (sections.some((section) => !groupIds.has(section.group_id))) {
  throw new Error('DistrictCoreData contains a section with an unknown group_id.')
}

groups.sort((left, right) => left.sort_order - right.sort_order)
sections.sort((left, right) => left.section_name.localeCompare(right.section_name, 'en-GB'))

const outputDirectory = resolve('src/generated')
await mkdir(outputDirectory, { recursive: true })
await writeFile(resolve(outputDirectory, 'core-data.json'), `${JSON.stringify({ groups, sections }, null, 2)}\n`)

console.log(`Prepared ${groups.length} groups and ${sections.length} sections for the webstore build.`)

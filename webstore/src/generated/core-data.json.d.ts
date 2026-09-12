declare const coreData: {
  groups: Array<{
    id: string
    group_name: string
    sort_order: number
    domains: string[]
    type: 'group' | 'district'
  }>
  sections: Array<{
    id: string
    group_id: string
    section_id: number
    section_name: string
    section_type: string
  }>
}

export default coreData

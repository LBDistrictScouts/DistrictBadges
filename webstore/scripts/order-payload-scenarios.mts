import { buildOrderPayload, type OrderCheckoutDetails } from '../src/orderPayload.ts'

const groupId = '4d5149f3-6214-4457-a04d-e428dc1200d7'
const sectionId = 'd9534dcb-a846-5a22-a2fe-b67580555563'
const primaryBadge = { id: 'f525eb6d-021c-4ef2-811f-feac8db8d35d', price: 1.5, quantity: 2 }
const secondBadge = { id: '0f3b8a4a-6c12-4f12-9a2e-0d9e4e4b2f70', price: 0, quantity: 1 }

const checkout = (overrides: Partial<OrderCheckoutDetails> = {}): OrderCheckoutDetails => ({
  firstName: 'Webstore',
  lastName: 'Customer',
  email: 'webstore@example.org',
  groupId,
  sectionId,
  postage: false,
  addressLine1: '',
  addressLine2: '',
  town: '',
  county: '',
  postcode: '',
  ...overrides,
})

const scenarios = [
  {
    name: 'collection',
    payload: buildOrderPayload(
      '10000000-0000-4000-8000-000000000001',
      checkout(),
      [primaryBadge],
    ),
  },
  {
    name: 'postage with required address fields',
    payload: buildOrderPayload(
      '10000000-0000-4000-8000-000000000002',
      checkout({
        email: 'postage-minimal@example.org',
        postage: true,
        addressLine1: '1 Scout Way',
        town: 'Chingford',
        postcode: 'E4 7QW',
      }),
      [primaryBadge],
    ),
  },
  {
    name: 'postage with optional address fields and multiple lines',
    payload: buildOrderPayload(
      '10000000-0000-4000-8000-000000000003',
      checkout({
        email: 'postage-full@example.org',
        postage: true,
        addressLine1: '2 Scout Way',
        addressLine2: 'Gilwell Park',
        town: 'Chingford',
        county: 'London',
        postcode: 'E4 7QW',
      }),
      [primaryBadge, secondBadge],
    ),
  },
]

process.stdout.write(JSON.stringify(scenarios))

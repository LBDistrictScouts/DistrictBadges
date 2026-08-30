export type OrderBasketItem = {
  id: string
  price: number
  quantity: number
}

export type OrderCheckoutDetails = {
  firstName: string
  lastName: string
  email: string
  groupId: string
  sectionId: string
  postage: boolean
  addressLine1: string
  addressLine2: string
  town: string
  county: string
  postcode: string
}

export type OrderPayload = {
  idempotency_key: string
  first_name: string
  last_name: string
  email: string
  group_id: string
  section_id: string
  postage: boolean
  dispatch_address?: {
    address_line_1: string
    address_line_2?: string
    town: string
    county?: string
    postcode: string
  }
  lines: Array<{
    badge_id: string
    quantity: number
    unit_price: number
  }>
}

export function buildOrderPayload(
  idempotencyKey: string,
  checkoutDetails: OrderCheckoutDetails,
  basket: OrderBasketItem[],
): OrderPayload {
  return {
    idempotency_key: idempotencyKey,
    first_name: checkoutDetails.firstName,
    last_name: checkoutDetails.lastName,
    email: checkoutDetails.email,
    group_id: checkoutDetails.groupId,
    section_id: checkoutDetails.sectionId,
    postage: checkoutDetails.postage,
    ...(checkoutDetails.postage ? {
      dispatch_address: {
        address_line_1: checkoutDetails.addressLine1,
        ...(checkoutDetails.addressLine2.trim() ? { address_line_2: checkoutDetails.addressLine2 } : {}),
        town: checkoutDetails.town,
        ...(checkoutDetails.county.trim() ? { county: checkoutDetails.county } : {}),
        postcode: checkoutDetails.postcode,
      },
    } : {}),
    lines: basket.map((item) => ({
      badge_id: item.id,
      quantity: item.quantity,
      unit_price: item.price,
    })),
  }
}

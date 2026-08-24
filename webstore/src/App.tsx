import { type FormEvent, useEffect, useMemo, useState } from 'react'
import { Alert, Badge, Button, Container, Form, Modal, Offcanvas, Spinner } from 'react-bootstrap'
import coreData from './generated/core-data.json'
import './App.css'

type Product = {
  objectID: string
  id: string
  badge_name: string
  national_product_code: string | number | null
  status: string | null
  available: boolean
  price: number
  reserve_quantity: number
  on_hand_quantity: number
  image_large_url: string | null
  image_medium_url: string | null
  section_tags: string[]
  type_tags: string[]
}

type BasketItem = {
  id: string
  name: string
  productCode: string
  price: number
  imageUrl: string | null
  quantity: number
}

type AlgoliaResponse = { hits: Product[]; nbHits: number }

type CheckoutDetails = {
  firstName: string
  lastName: string
  email: string
  groupId: string
  sectionId: string
}

const BASKET_STORAGE_KEY = 'district-badges:basket'
const CHECKOUT_DETAILS_STORAGE_KEY = 'district-badges:checkout-details'
const appId = import.meta.env.VITE_ALGOLIA_APP_ID?.trim()
const searchApiKey = import.meta.env.VITE_ALGOLIA_SEARCH_API_KEY?.trim()
const indexName = import.meta.env.VITE_ALGOLIA_BADGES_INDEX?.trim() || 'BADGES'
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL?.trim().replace(/\/$/, '')

function readBasket(): BasketItem[] {
  try {
    const value = window.localStorage.getItem(BASKET_STORAGE_KEY)
    const parsed = value ? JSON.parse(value) as unknown : []
    return Array.isArray(parsed) ? parsed as BasketItem[] : []
  } catch {
    return []
  }
}

function readCheckoutDetails(): CheckoutDetails {
  const emptyDetails: CheckoutDetails = {
    firstName: '',
    lastName: '',
    email: '',
    groupId: '',
    sectionId: '',
  }

  try {
    const value = window.localStorage.getItem(CHECKOUT_DETAILS_STORAGE_KEY)
    const parsed = value ? JSON.parse(value) as Partial<CheckoutDetails> : {}
    const groupId = typeof parsed.groupId === 'string' && coreData.groups.some((group) => group.id === parsed.groupId)
      ? parsed.groupId
      : ''
    const sectionId = typeof parsed.sectionId === 'string' && coreData.sections.some((section) => (
      section.id === parsed.sectionId && section.group_id === groupId
    )) ? parsed.sectionId : ''

    return {
      firstName: typeof parsed.firstName === 'string' ? parsed.firstName : '',
      lastName: typeof parsed.lastName === 'string' ? parsed.lastName : '',
      email: typeof parsed.email === 'string' ? parsed.email : '',
      groupId,
      sectionId,
    }
  } catch {
    return emptyDetails
  }
}

function formatPrice(price: number) {
  return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(price)
}

function SearchIcon() {
  return <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="m16.2 16.2 4.3 4.3" /></svg>
}

function BasketIcon() {
  return <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.5L21 7H6" /><circle cx="9.5" cy="20" r="1" /><circle cx="18" cy="20" r="1" /></svg>
}

function App() {
  const [query, setQuery] = useState('')
  const [section, setSection] = useState('All sections')
  const [products, setProducts] = useState<Product[]>([])
  const [heroBadgeImages, setHeroBadgeImages] = useState<string[]>([])
  const [totalHits, setTotalHits] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [basket, setBasket] = useState<BasketItem[]>(readBasket)
  const [basketOpen, setBasketOpen] = useState(false)
  const [addedProductId, setAddedProductId] = useState<string | null>(null)
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null)
  const [selectedQuantity, setSelectedQuantity] = useState(1)
  const [checkoutOpen, setCheckoutOpen] = useState(false)
  const [checkoutStatus, setCheckoutStatus] = useState<'idle' | 'submitting' | 'success'>('idle')
  const [checkoutError, setCheckoutError] = useState<string | null>(null)
  const [checkoutDetails, setCheckoutDetails] = useState<CheckoutDetails>(readCheckoutDetails)

  useEffect(() => {
    try {
      window.localStorage.setItem(BASKET_STORAGE_KEY, JSON.stringify(basket))
    } catch {
      // The in-memory basket still works when browser storage is unavailable.
    }
  }, [basket])

  useEffect(() => {
    try {
      window.localStorage.setItem(CHECKOUT_DETAILS_STORAGE_KEY, JSON.stringify(checkoutDetails))
    } catch {
      // Checkout remains usable for this session when browser storage is unavailable.
    }
  }, [checkoutDetails])

  useEffect(() => {
    const controller = new AbortController()
    const timer = window.setTimeout(async () => {
      if (!appId || !searchApiKey) {
        setProducts([])
        setTotalHits(0)
        setLoading(false)
        setError('The badge catalogue has not been configured yet. Add the public Algolia settings to .env.local.')
        return
      }

      setLoading(true)
      setError(null)
      try {
        const response = await fetch(`https://${appId}-dsn.algolia.net/1/indexes/${encodeURIComponent(indexName)}/query`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Algolia-Application-Id': appId,
            'X-Algolia-API-Key': searchApiKey,
          },
          body: JSON.stringify({ query, hitsPerPage: 80 }),
          signal: controller.signal,
        })
        if (!response.ok) throw new Error(`Algolia returned ${response.status}`)
        const result = await response.json() as AlgoliaResponse
        setProducts(result.hits)
        setTotalHits(result.nbHits)
        setHeroBadgeImages((currentImages) => {
          if (currentImages.length > 0) return currentImages
          return Array.from(new Set(result.hits
            .map((product) => product.image_medium_url)
            .filter((imageUrl): imageUrl is string => Boolean(imageUrl))))
            .slice(0, 8)
        })
      } catch (requestError) {
        if (requestError instanceof DOMException && requestError.name === 'AbortError') return
        setProducts([])
        setTotalHits(0)
        setError('We could not load the badge catalogue. Please try again shortly.')
      } finally {
        if (!controller.signal.aborted) setLoading(false)
      }
    }, query ? 250 : 0)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [query])

  const sections = useMemo(() => {
    const names = new Set(products.flatMap((product) => product.section_tags ?? []))
    return ['All sections', ...Array.from(names).sort()]
  }, [products])

  const visibleProducts = useMemo(() => section === 'All sections'
    ? products
    : products.filter((product) => product.section_tags?.includes(section)), [products, section])

  const itemCount = basket.reduce((count, item) => count + item.quantity, 0)
  const basketTotal = basket.reduce((total, item) => total + item.price * item.quantity, 0)
  const checkoutSections = coreData.sections.filter((item) => item.group_id === checkoutDetails.groupId)

  function openQuantityPicker(product: Product) {
    const existingItem = basket.find((item) => item.id === product.id)
    setSelectedProduct(product)
    setSelectedQuantity(existingItem?.quantity ?? 1)
  }

  function confirmQuantity() {
    if (!selectedProduct) return

    const product = selectedProduct
    setBasket((current) => {
      const existing = current.find((item) => item.id === product.id)
      if (existing) return current.map((item) => item.id === product.id ? { ...item, quantity: selectedQuantity } : item)
      return [...current, {
        id: product.id,
        name: product.badge_name,
        productCode: String(product.national_product_code ?? ''),
        price: product.price,
        imageUrl: product.image_medium_url,
        quantity: selectedQuantity,
      }]
    })
    setAddedProductId(product.id)
    setSelectedProduct(null)
    window.setTimeout(() => setAddedProductId(null), 1200)
  }

  function changeQuantity(id: string, quantity: number) {
    setBasket((current) => current
      .map((item) => item.id === id ? { ...item, quantity } : item)
      .filter((item) => item.quantity > 0))
  }

  function openCheckout() {
    setBasketOpen(false)
    setCheckoutStatus('idle')
    setCheckoutError(null)
    setCheckoutOpen(true)
  }

  function updateCheckoutField(field: keyof CheckoutDetails, value: string) {
    setCheckoutDetails((current) => ({
      ...current,
      [field]: value,
      ...(field === 'groupId' ? { sectionId: '' } : {}),
    }))
  }

  async function submitOrder(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!apiBaseUrl) {
      setCheckoutError('The order service has not been configured. Please contact the district team.')
      return
    }

    setCheckoutStatus('submitting')
    setCheckoutError(null)
    try {
      const response = await fetch(`${apiBaseUrl}/api/orders.json`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          first_name: checkoutDetails.firstName,
          last_name: checkoutDetails.lastName,
          email: checkoutDetails.email,
          group_id: checkoutDetails.groupId,
          section_id: checkoutDetails.sectionId,
          lines: basket.map((item) => ({ badge_id: item.id, quantity: item.quantity })),
        }),
      })

      if (!response.ok) {
        const result = await response.json().catch(() => null) as { message?: string } | null
        throw new Error(result?.message || 'The order could not be accepted.')
      }

      setBasket([])
      setCheckoutStatus('success')
    } catch (submissionError) {
      setCheckoutStatus('idle')
      setCheckoutError(submissionError instanceof Error ? submissionError.message : 'The order could not be accepted.')
    }
  }

  return (
    <div className="storefront">
      <header className="site-header">
        <Container className="header-inner">
          <a className="district-brand" href="#top" aria-label="LBA Scouts district badge shop home">
            <span className="brand-mark" aria-hidden="true">LBA</span>
            <span><strong>LBA Scouts</strong><small>District Badge Shop</small></span>
          </a>
          <nav className="desktop-nav" aria-label="Main navigation">
            <a className="active" href="#shop">Shop badges</a>
            <a href="#how-it-works">How it works</a>
            <a href="#support">Help</a>
          </nav>
          <Button className="basket-button" variant="primary" onClick={() => setBasketOpen(true)}>
            <BasketIcon /><span>Basket</span><Badge bg="light" text="dark" pill>{itemCount}</Badge>
          </Button>
        </Container>
      </header>

      <main id="top">
        <section className="shop-hero">
          <div className="hero-badge-collage" aria-hidden="true">
            {heroBadgeImages.map((imageUrl) => <img key={imageUrl} src={imageUrl} alt="" />)}
          </div>
          <Container className="hero-content">
            <div className="eyebrow">Letchworth, Baldock &amp; Ashwell Scouts</div>
            <h1>Badges for every<br /><span>Scouting adventure.</span></h1>
            <p>Order official Scout badges from your district shop. Search the range, build your basket and collect from the district team.</p>
            <div className="hero-search" role="search">
              <SearchIcon />
              <Form.Control type="search" value={query} onChange={(event) => { setQuery(event.target.value); setSection('All sections') }} placeholder="Search by badge name…" aria-label="Search badges" />
              {query && <Button variant="link" onClick={() => setQuery('')}>Clear</Button>}
            </div>
            <div className="hero-promises" aria-label="Shop benefits">
              <span><b>✓</b> Order any badge</span><span><b>✓</b> Group ordering</span><span><b>✓</b> Local collection</span>
            </div>
          </Container>
        </section>

        <section className="catalogue" id="shop">
          <Container>
            <div className="catalogue-heading">
              <div>
                <div className="eyebrow">Badge catalogue</div>
                <h2>{query ? `Results for “${query}”` : 'Find the badges you need'}</h2>
                <p>{loading ? 'Searching the district catalogue…' : `${visibleProducts.length} of ${totalHits} badges shown`}</p>
              </div>
              <Form.Group className="section-filter" controlId="section-filter">
                <Form.Label>Filter by section</Form.Label>
                <Form.Select value={section} onChange={(event) => setSection(event.target.value)}>
                  {sections.map((name) => <option key={name}>{name}</option>)}
                </Form.Select>
              </Form.Group>
            </div>

            {error && <Alert variant="warning" className="catalogue-alert">{error}</Alert>}
            {loading ? (
              <div className="catalogue-state" aria-live="polite"><Spinner animation="border" role="status" /><span>Loading badges…</span></div>
            ) : visibleProducts.length > 0 ? (
              <div className="product-grid">
                {visibleProducts.map((product) => (
                  <article className="product-card" key={product.objectID}>
                    <div className="product-image-wrap">
                      {product.image_medium_url ? <img src={product.image_medium_url} alt="" loading="lazy" /> : <div className="image-placeholder" aria-hidden="true">Badge</div>}
                    </div>
                    <div className="product-content">
                      <div className="product-tags">{(product.section_tags ?? []).slice(0, 2).map((tag) => <span key={tag}>{tag}</span>)}</div>
                      <h3>{product.badge_name}</h3>
                      <div className="product-footer">
                        <strong>{formatPrice(product.price)}</strong>
                        <Button variant={addedProductId === product.id ? 'success' : 'primary'} onClick={() => openQuantityPicker(product)} aria-label={`Choose a quantity of ${product.badge_name}`}>
                          {addedProductId === product.id ? 'Added ✓' : 'Add to basket'}
                        </Button>
                      </div>
                    </div>
                  </article>
                ))}
              </div>
            ) : !error && (
              <div className="catalogue-state"><span className="empty-icon" aria-hidden="true">⌕</span><h3>No matching badges</h3><p>Try another badge name or section.</p><Button variant="outline-primary" onClick={() => { setQuery(''); setSection('All sections') }}>Clear filters</Button></div>
            )}
          </Container>
        </section>

        <section className="how-it-works" id="how-it-works">
          <Container>
            <div className="eyebrow">Simple district ordering</div><h2>From basket to your badge box</h2>
            <div className="steps-grid">
              <article><span>01</span><h3>Choose your badges</h3><p>Search the district catalogue and add the quantities your group needs.</p></article>
              <article><span>02</span><h3>Place your order</h3><p>Each section can review its basket and order the badges it needs independently.</p></article>
              <article><span>03</span><h3>Collect locally</h3><p>The district team prepares your order and lets you know when it is ready.</p></article>
              <article><span>04</span><h3>Treasurers invoiced</h3><p>Your Group Treasurer is invoiced monthly for the badges fulfilled and collected—not simply the quantities ordered.</p></article>
            </div>
          </Container>
        </section>
      </main>

      <footer id="support">
        <Container>
          <div className="footerGrid">
            <div className="footerBrand">
              <a className="footer-brand-link" href="https://lbdscouts.org.uk">
                <span className="footer-brand-mark" aria-hidden="true">LBA</span>
                <span>LBA Scouts</span>
              </a>
              <p>Preparing young people with<br /><strong>#SkillsForLife</strong></p>
            </div>
            <div>
              <h3>District services</h3>
              <a href="https://lbdscouts.org.uk">District website</a>
              <a href="https://lbdscouts.org.uk/#resources">Co-Ordinator newsletter</a>
              <a href="https://lbdscouts.org.uk/#resources">District Headquarters</a>
              <a href="https://lbdscouts.org.uk/#resources">District Minibus</a>
            </div>
            <div>
              <h3>Scouting</h3>
              <a href="https://www.scouts.org.uk">National Scouts website</a>
              <a href="https://lbdscouts.org.uk/#projects">Join Scouts</a>
              <a href="https://lbdscouts.org.uk/#projects">Volunteer</a>
              <a href="https://lbdscouts.org.uk/team">District team</a>
            </div>
            <div>
              <h3>Useful links</h3>
              <a href="https://lbdscouts.org.uk/#contact">Contact us</a>
              <a href="https://lbdscouts.org.uk/#contact">Policies</a>
              <span>Registered Charity: 279860</span>
            </div>
          </div>
          <div className="footerBottom">
            <span>© {new Date().getFullYear()} Letchworth, Baldock &amp; Ashwell Scouts.</span>
            <span>#SkillsForLife</span>
          </div>
        </Container>
      </footer>

      <Modal show={selectedProduct !== null} onHide={() => setSelectedProduct(null)} centered className="quantity-modal">
        <Modal.Header closeButton>
          <Modal.Title>Choose quantity</Modal.Title>
        </Modal.Header>
        {selectedProduct && (
          <Modal.Body>
            <div className="quantity-product">
              <div className="quantity-product-image">
                {selectedProduct.image_medium_url ? <img src={selectedProduct.image_medium_url} alt="" /> : <span>Badge</span>}
              </div>
              <div><h3>{selectedProduct.badge_name}</h3><p>{formatPrice(selectedProduct.price)} each</p></div>
            </div>
            <div className="quantity-picker" aria-label={`Quantity of ${selectedProduct.badge_name}`}>
              <Button variant="outline-secondary" onClick={() => setSelectedQuantity((quantity) => Math.max(1, quantity - 1))} disabled={selectedQuantity === 1} aria-label="Decrease quantity">−</Button>
              <Form.Control
                type="number"
                min="1"
                inputMode="numeric"
                value={selectedQuantity}
                onFocus={(event) => event.currentTarget.select()}
                onChange={(event) => setSelectedQuantity(Math.max(1, Number(event.currentTarget.value) || 1))}
                aria-label={`Quantity of ${selectedProduct.badge_name}`}
              />
              <Button variant="outline-secondary" onClick={() => setSelectedQuantity((quantity) => quantity + 1)} aria-label="Increase quantity">+</Button>
            </div>
            <div className="quantity-total"><span>Total</span><strong>{formatPrice(selectedProduct.price * selectedQuantity)}</strong></div>
            <Button size="lg" className="w-100" onClick={confirmQuantity}>
              {basket.some((item) => item.id === selectedProduct.id) ? 'Update basket' : 'Add to basket'}
            </Button>
          </Modal.Body>
        )}
      </Modal>

      <Modal show={checkoutOpen} onHide={() => checkoutStatus !== 'submitting' && setCheckoutOpen(false)} centered size="lg" className="checkout-modal">
        <Modal.Header closeButton={checkoutStatus !== 'submitting'}>
          <Modal.Title>{checkoutStatus === 'success' ? 'Order confirmed' : 'Checkout'}</Modal.Title>
        </Modal.Header>
        {checkoutStatus === 'success' ? (
          <Modal.Body className="checkout-success">
            <span aria-hidden="true">✓</span>
            <h2>Thank you, {checkoutDetails.firstName}.</h2>
            <p>Your order has been received by the district badge shop. We’ll contact you at <strong>{checkoutDetails.email}</strong> when badges are ready to collect.</p>
            <p>Your Group Treasurer will be invoiced monthly for the badges that are fulfilled and collected.</p>
            <Button onClick={() => setCheckoutOpen(false)}>Continue shopping</Button>
          </Modal.Body>
        ) : (
          <Form onSubmit={submitOrder}>
            <Modal.Body>
              <div className="checkout-grid">
                <div className="checkout-form-fields">
                  <div><span className="checkout-step">Your details</span><h2>Who is placing this order?</h2><p>We’ll use these details to arrange collection.</p></div>
                  {checkoutError && <Alert variant="danger">{checkoutError}</Alert>}
                  <div className="checkout-name-row">
                    <Form.Group controlId="checkout-first-name"><Form.Label>First name</Form.Label><Form.Control required autoComplete="given-name" value={checkoutDetails.firstName} onChange={(event) => updateCheckoutField('firstName', event.target.value)} /></Form.Group>
                    <Form.Group controlId="checkout-last-name"><Form.Label>Last name</Form.Label><Form.Control required autoComplete="family-name" value={checkoutDetails.lastName} onChange={(event) => updateCheckoutField('lastName', event.target.value)} /></Form.Group>
                  </div>
                  <Form.Group controlId="checkout-email"><Form.Label>Email address</Form.Label><Form.Control required type="email" autoComplete="email" value={checkoutDetails.email} onChange={(event) => updateCheckoutField('email', event.target.value)} /></Form.Group>
                  <Form.Group controlId="checkout-group"><Form.Label>Scout Group</Form.Label><Form.Select required value={checkoutDetails.groupId} onChange={(event) => updateCheckoutField('groupId', event.target.value)}><option value="">Choose your Group…</option>{coreData.groups.map((group) => <option key={group.id} value={group.id}>{group.group_name}</option>)}</Form.Select></Form.Group>
                  <Form.Group controlId="checkout-section"><Form.Label>Section</Form.Label><Form.Select required disabled={!checkoutDetails.groupId} value={checkoutDetails.sectionId} onChange={(event) => updateCheckoutField('sectionId', event.target.value)}><option value="">{checkoutDetails.groupId ? 'Choose your Section…' : 'Choose a Group first'}</option>{checkoutSections.map((item) => <option key={item.id} value={item.id}>{item.section_name}</option>)}</Form.Select></Form.Group>
                </div>
                <aside className="checkout-summary">
                  <span className="checkout-step">Order summary</span>
                  <h2>{itemCount} {itemCount === 1 ? 'badge' : 'badges'}</h2>
                  <div className="checkout-summary-items">
                    {basket.map((item) => <div key={item.id}><span><b>{item.quantity}×</b> {item.name}</span><strong>{formatPrice(item.price * item.quantity)}</strong></div>)}
                  </div>
                  <div className="checkout-summary-total"><span>Total</span><strong>{formatPrice(basketTotal)}</strong></div>
                  <p>You’ll only be invoiced for badges that are fulfilled and collected.</p>
                </aside>
              </div>
            </Modal.Body>
            <Modal.Footer><Button variant="outline-secondary" disabled={checkoutStatus === 'submitting'} onClick={() => { setCheckoutOpen(false); setBasketOpen(true) }}>Back to basket</Button><Button type="submit" size="lg" disabled={checkoutStatus === 'submitting'}>{checkoutStatus === 'submitting' ? <><Spinner size="sm" /> Sending order…</> : 'Confirm order'}</Button></Modal.Footer>
          </Form>
        )}
      </Modal>

      <Offcanvas show={basketOpen} onHide={() => setBasketOpen(false)} placement="end" className="basket-drawer">
        <Offcanvas.Header closeButton><Offcanvas.Title>Your basket <Badge bg="primary" pill>{itemCount}</Badge></Offcanvas.Title></Offcanvas.Header>
        <Offcanvas.Body>
          {basket.length === 0 ? (
            <div className="empty-basket"><BasketIcon /><h3>Your basket is empty</h3><p>Add badges from the catalogue and they will be saved on this device.</p><Button onClick={() => setBasketOpen(false)}>Browse badges</Button></div>
          ) : (
            <div className="basket-layout">
              <div className="basket-items">
                {basket.map((item) => (
                  <article className="basket-item" key={item.id}>
                    <div className="basket-thumb">{item.imageUrl ? <img src={item.imageUrl} alt="" /> : <span>Badge</span>}</div>
                    <div className="basket-item-detail">
                      <h3>{item.name}</h3><p>{item.productCode ? `Code ${item.productCode}` : 'District badge'}</p><strong>{formatPrice(item.price)}</strong>
                      <div className="quantity-control" aria-label={`Quantity for ${item.name}`}><Button variant="outline-secondary" onClick={() => changeQuantity(item.id, item.quantity - 1)} aria-label="Decrease quantity">−</Button><span>{item.quantity}</span><Button variant="outline-secondary" onClick={() => changeQuantity(item.id, item.quantity + 1)} aria-label="Increase quantity">+</Button></div>
                    </div>
                    <Button className="remove-item" variant="link" onClick={() => changeQuantity(item.id, 0)}>Remove</Button>
                  </article>
                ))}
              </div>
              <div className="basket-summary"><div><span>Subtotal</span><strong>{formatPrice(basketTotal)}</strong></div><p>Badges not currently held by the district shop will be ordered for you.</p><Button size="lg" className="w-100" onClick={openCheckout}>Continue to checkout</Button></div>
            </div>
          )}
        </Offcanvas.Body>
      </Offcanvas>
    </div>
  )
}

export default App

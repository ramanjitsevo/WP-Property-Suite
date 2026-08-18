import React, { useState, useEffect, useRef } from 'react';
import { Map, Marker } from '@vis.gl/react-google-maps';
import './PropertySingle.css';

const STATUS_LABELS = {
  'for-sale': 'For Sale',
  'for-rent': 'For Rent',
  'sold': 'Sold',
  'rented': 'Rented',
};

const FEATURE_EXCLUDE_FIELDS = [
  'id',
  'title',
  'content',
  'excerpt',
  'date',
  'thumbnail',
  'thumbnail_url',
  'price',
  'area',
  'address',
  'city',
  'state',
  'zipcode',
  'country',
  'latitude',
  'longitude',
  'lat',
  'lng',
  'status',
  'property_type',
  'location',
  'bedrooms',
  'bathrooms',
  'floor',
  'gallery',
  'gallery_urls',
  'agent',
  'agent_name',
  'agent_phone',
  'agent_email',
  'agent_photo',
  'agentName',
  'agentPhone',
  'agentEmail',
  'agentPhoto',
  'additional',
  'additional_details',
  'additionalDetails',
  'faq',
  'faqs',
  'property_faqs',
  'propertyFaqs',
];

const PROPERTY_PLACEHOLDER_IMAGE = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22800%22%20height%3D%22540%22%20viewBox%3D%220%200%20800%20540%22%3E%3Crect%20width%3D%22800%22%20height%3D%22540%22%20fill%3D%22%23e2e8f0%22%2F%3E%3Cpath%20d%3D%22M140%20365h520v75H140zM215%20270l185-140%20185%20140v170H215z%22%20fill%3D%22%23cbd5e1%22%2F%3E%3Cpath%20d%3D%22M340%20440V315h120v125%22%20fill%3D%22%23f8fafc%22%2F%3E%3C%2Fsvg%3E';

const normalizeArrayField = (rawValue) => {
  if (Array.isArray(rawValue)) return rawValue;
  if (typeof rawValue === 'string' && rawValue.trim()) {
    try {
      const parsed = JSON.parse(rawValue);
      return Array.isArray(parsed) ? parsed : [parsed];
    } catch (e) {
      return [rawValue];
    }
  }
  if (rawValue && typeof rawValue === 'object') return [rawValue];
  return [];
};

const getPropertyInquiryMessage = (property) => {
  const title = property?.title || 'this property';
  const details = [
    property?.price ? `Price: ${property.price}` : '',
    [property?.address, property?.city, property?.state].filter(Boolean).join(', '),
  ].filter(Boolean);

  const pageUrl = typeof window !== 'undefined' ? window.location.href : '';

  return [
    `Hi, I am interested in ${title}.`,
    details.length ? details.join(' | ') : '',
    pageUrl ? `Property link: ${pageUrl}` : '',
    'Please share more details and availability for a visit.',
  ].filter(Boolean).join('\n');
};

const getPropertySlug = (property) => {
  const title = property?.title || 'property';
  const slug = title
    .toString()
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return `${slug || 'property'}-${property?.id || ''}`.replace(/-$/, '');
};

function PropertySingle({ property, onBack, settings }) {
  const [activeTab, setActiveTab] = useState('overview');
  const [mainImage, setMainImage] = useState(0);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [submitError, setSubmitError] = useState('');
  const [copyToast, setCopyToast] = useState('');
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    message: getPropertyInquiryMessage(property),
  });

  const contactFormRef = useRef(null);
  const statusLabel = STATUS_LABELS[property?.status] || property?.status || 'For Sale';
  const latitude = parseFloat(property?.latitude ?? property?.lat);
  const longitude = parseFloat(property?.longitude ?? property?.lng);
  const hasMapCoordinates = Number.isFinite(latitude) && Number.isFinite(longitude);
  const mapCenter = hasMapCoordinates ? { lat: latitude, lng: longitude } : null;
  const canRenderMap = hasMapCoordinates && Boolean(settings?.googleApiKey);

  const galleryImages = (() => {
    if (property?.gallery && property.gallery.length > 0) {
      const thumb = property.thumbnail;
      return thumb && !property.gallery.includes(thumb)
        ? [thumb, ...property.gallery]
        : property.gallery;
    }
    return [property?.thumbnail || PROPERTY_PLACEHOLDER_IMAGE];
  })();

  const additionalDetails = normalizeArrayField(
    property?.additional_details || property?.additionalDetails || property?.additional || null
  );

  const customFaqItems = normalizeArrayField(property?.faqs || property?.faq || property?.property_faqs || property?.propertyFaqs)
    .map((item, index) => {
      if (item === null || item === undefined) return null;
      if (typeof item === 'object') {
        const question = item.question || item.title || item.label || `Question ${index + 1}`;
        const answer = item.answer || item.value || item.text || item.description || '';
        return question && answer ? { question, answer } : null;
      }
      return { question: `Question ${index + 1}`, answer: String(item) };
    })
    .filter(Boolean);

  const fallbackFaqItems = [
    {
      question: `What is the price of ${property?.title || 'this property'}?`,
      answer: property?.price ? `The listed price is ${property.price}.` : 'Please request information for the latest property price.',
    },
    {
      question: 'Where is this property located?',
      answer: [property?.address, property?.city, property?.state, property?.zipcode, property?.country].filter(Boolean).join(', ') || 'The exact location details are available on request.',
    },
    {
      question: 'How can I schedule a property visit?',
      answer: 'Use the Schedule a Tour button or submit the inquiry form on this page to request a visit.',
    },
    {
      question: 'What are the key property features?',
      answer: [
        property?.bedrooms ? `${property.bedrooms} bedroom(s)` : '',
        property?.bathrooms ? `${property.bathrooms} bathroom(s)` : '',
        property?.area ? `${property.area} sq ft` : '',
      ].filter(Boolean).join(', ') || 'Key features are listed in the Features tab.',
    },
  ];

  const faqItems = customFaqItems.length ? customFaqItems : fallbackFaqItems;

  const propertyAgent = property?.agent || {};
  const activeAgent = {
    name: propertyAgent.name || property?.agent_name || property?.agentName || settings?.agentName || 'Default Agent',
    role: propertyAgent.role || property?.agent_role || property?.agentRole || settings?.agentRole || 'Property Agent',
    phone: propertyAgent.phone || property?.agent_phone || property?.agentPhone || settings?.agentPhone || '',
    email: propertyAgent.email || property?.agent_email || property?.agentEmail || settings?.agentEmail || settings?.contactEmail || '',
    photo: propertyAgent.photo || property?.agent_photo || property?.agentPhoto || settings?.agentPhoto || '',
  };

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'features', label: 'Features' },
    { id: 'location', label: 'Location' },
    { id: 'additional', label: 'Additional Details' },
    { id: 'faq', label: 'FAQ' },
  ];

  useEffect(() => {
    if (property?.id) {
      const favs = JSON.parse(localStorage.getItem('property_favorites') || '[]');
      setIsFavorite(favs.includes(property.id));
    }
  }, [property?.id]);

  const handleInputChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const scrollToContact = () => {
    contactFormRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const handleScheduleTour = () => {
    if (settings?.scheduleTourUrl) {
      window.open(settings.scheduleTourUrl, '_blank');
    } else {
      scrollToContact();
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitError('');
    setSubmitSuccess(false);

    const payload = {
      name:          formData.name,
      email:         formData.email,
      phone:          formData.phone,
      message:       formData.message,
      propertyId:    property?.id || 0,
      propertyTitle: property?.title || '',
      website:       '',
    };

    try {
      const data = await fetch(`${propertyPluginData.apiUrl}/leads`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': propertyPluginData.nonce,
        },
        body: JSON.stringify(payload),
      });
      const result = await data.json();
      if (!data.ok) throw new Error(result.message || `Server error (${data.status})`);
      setSubmitSuccess(true);
      setFormData({ name: '', email: '', phone: '', message: getPropertyInquiryMessage(property) });
    } catch (err) {
      setSubmitError(err.message || 'Something went wrong. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const toggleFavorite = () => {
    const favs = JSON.parse(localStorage.getItem('property_favorites') || '[]');
    let updated;
    if (isFavorite) {
      updated = favs.filter(id => id !== property.id);
    } else {
      updated = [...favs, property.id];
    }
    localStorage.setItem('property_favorites', JSON.stringify(updated));
    setIsFavorite(!isFavorite);
  };

  // SEO Helper functions
  const getPageUrl = () => {
    if (typeof window === 'undefined') return '';
    const url = new URL(window.location.href);
    url.searchParams.delete('property');
    url.searchParams.set('wps_property', getPropertySlug(property));
    return url.toString();
  };
  const getPageTitle = () => property?.title || 'Check out this property';
  const getPropertyDescription = () => {
    if (property?.content) {
      // Strip HTML tags for meta description
      const temp = document.createElement('div');
      temp.innerHTML = property.content;
      const text = temp.textContent || temp.innerText || '';
      return text.substring(0, 160);
    }
    return `${property?.title || 'Property'} located at ${property?.address || ''}, ${property?.city || ''}. ${property?.bedrooms || ''} bed, ${property?.bathrooms || ''} bath, ${property?.area || ''} sq ft.`;
  };

  const getCanonicalUrl = () => getPageUrl();

  // Update document head for SEO
  useEffect(() => {
    if (!property?.title) return;

    // Update page title
    const originalTitle = document.title;
    document.title = `${property.title} | ${property.address ? property.address + ', ' : ''}${property.city || ''}`;

    // Update meta description
    let metaDesc = document.querySelector('meta[name="description"]');
    if (!metaDesc) {
      metaDesc = document.createElement('meta');
      metaDesc.name = 'description';
      document.head.appendChild(metaDesc);
    }
    metaDesc.content = getPropertyDescription();

    // Update canonical URL
    let canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) {
      canonical = document.createElement('link');
      canonical.rel = 'canonical';
      document.head.appendChild(canonical);
    }
    canonical.href = getCanonicalUrl();

    // Update Open Graph tags
    const ogTags = {
      'og:title': `${property.title}`,
      'og:description': getPropertyDescription(),
      'og:type': 'article',
      'og:url': getPageUrl(),
      'og:image': galleryImages[0] || property.thumbnail || '',
      'og:locale': 'en_US',
    };

    Object.entries(ogTags).forEach(([property_name, content]) => {
      let meta = document.querySelector(`meta[property="${property_name}"]`);
      if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('property', property_name);
        document.head.appendChild(meta);
      }
      meta.content = content;
    });

    // Update Twitter Card tags
    const twitterTags = {
      'twitter:card': 'summary_large_image',
      'twitter:title': property.title,
      'twitter:description': getPropertyDescription(),
      'twitter:image': galleryImages[0] || property.thumbnail || '',
    };

    Object.entries(twitterTags).forEach(([name, content]) => {
      let meta = document.querySelector(`meta[name="${name}"]`);
      if (!meta) {
        meta = document.createElement('meta');
        meta.name = name;
        document.head.appendChild(meta);
      }
      meta.content = content;
    });

    // Cleanup on unmount
    return () => {
      document.title = originalTitle;
    };
  }, [property?.title, property?.address, property?.city, property?.content]);

  const handleShare = async () => {
    const url = getPageUrl();
    try {
      await navigator.clipboard.writeText(url);
      setCopyToast('Property link copied');
    } catch (err) {
      const fallback = document.createElement('textarea');
      fallback.value = url;
      fallback.setAttribute('readonly', '');
      fallback.style.position = 'fixed';
      fallback.style.left = '-9999px';
      document.body.appendChild(fallback);
      fallback.select();
      document.execCommand('copy');
      document.body.removeChild(fallback);
      setCopyToast('Property link copied');
    }
  };

  useEffect(() => {
    if (!copyToast) return undefined;
    const timer = window.setTimeout(() => setCopyToast(''), 2400);
    return () => window.clearTimeout(timer);
  }, [copyToast]);

  const renderBottomBar = () => (
    <div className="property-bottom-bar property-bottom-bar-spaced">
      <div className="bottom-price">
        <span className="price-amount">{property.price}</span>
        <span className="price-badge">{statusLabel}</span>
      </div>
      <div className="bottom-actions">
        <a href={`tel:${(activeAgent.phone || '').replace(/[^+\d]/g, '')}`} className="btn-call">
          <i className="fas fa-phone-flip"></i> {activeAgent.phone || 'Call Agent'}
        </a>
        {/* <button className="btn-schedule" onClick={handleScheduleTour}>
          <i className="far fa-calendar-check"></i> Schedule a Tour
        </button> */}
        {/* <button className="btn-request-info" onClick={scrollToContact}>
          Request Information
        </button> */}
      </div>
    </div>
  );

  const renderLocationContent = ({ compact = false } = {}) => {
    if (canRenderMap) {
      return (
        <div className={`property-google-map${compact ? ' property-google-map-compact' : ''}`}>
          <Map
            defaultCenter={mapCenter}
            defaultZoom={15}
            gestureHandling="greedy"
            disableDefaultUI={false}
          >
            <Marker position={mapCenter} title={property.title || 'Property location'} />
          </Map>
        </div>
      );
    }

    return (
      <div className={`map-placeholder${compact ? ' map-placeholder-compact' : ''}`}>
        {property.address ? (
          <address className="map-address" itemProp="address" itemScope itemType="https://schema.org/PostalAddress">
            <div className={`map-pin${compact ? ' map-pin-compact' : ''}`}><i className="fas fa-map-marker-alt"></i></div>
            <p className={`map-address-line${compact ? ' map-address-line-large' : ''}`} itemProp="streetAddress">{property.address}</p>
            <p className="map-address-region">
              <span itemProp="addressLocality">{property.city}</span>
              {property.city && property.state && ', '}
              <span itemProp="addressRegion">{property.state}</span>
              {property.state && property.zipcode && (compact ? ', ' : ' ')}
              <span itemProp="postalCode">{property.zipcode}</span>
              {property.zipcode && property.country && ', '}
              <span itemProp="addressCountry">{property.country}</span>
            </p>
          </address>
        ) : (
          <>
            <div className="map-pin"><i className="fas fa-map-marker-alt"></i></div>
            <p>Address not available</p>
          </>
        )}
      </div>
    );
  };

  if (!property) {
    return <div className="wps-loading">Loading property details...</div>;
  }

  return (
    <article className="property-single-page" itemScope itemType="https://schema.org/RealEstateListing">
      {/* Enhanced JSON-LD Structured Data */}
      <script type="application/ld+json">
        {JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'RealEstateListing',
          'name': property.title,
          'description': getPropertyDescription(),
          'url': getCanonicalUrl(),
          'image': galleryImages,
          'price': property.price,
          'priceCurrency': 'USD',
          'address': {
            '@type': 'PostalAddress',
            'streetAddress': property.address || '',
            'addressLocality': property.city || '',
            'addressRegion': property.state || '',
            'postalCode': property.zipcode || '',
            'addressCountry': property.country || '',
          },
          'numberOfRooms': property.bedrooms || 0,
          'numberOfBathroomsTotal': property.bathrooms || 0,
          'floorSize': {
            '@type': 'QuantitativeValue',
            'value': property.area || 0,
            'unitCode': 'MTK',
          },
          'propertyType': property.property_type || 'Residential',
          'datePosted': property.date || new Date().toISOString().split('T')[0],
        })}
      </script>

      {/* FAQ Structured Data */}
      {faqItems.length > 0 && (
        <script type="application/ld+json">
          {JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': faqItems.map((item) => ({
              '@type': 'Question',
              'name': item.question,
              'acceptedAnswer': {
                '@type': 'Answer',
                'text': item.answer,
              },
            })),
          })}
        </script>
      )}

      {/* Breadcrumb Structured Data */}
      <script type="application/ld+json">
        {JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'BreadcrumbList',
          'itemListElement': [
            {
              '@type': 'ListItem',
              'position': 1,
              'name': 'Home',
              'item': window.location.origin,
            },
            {
              '@type': 'ListItem',
              'position': 2,
              'name': 'Properties',
              'item': window.location.origin + '/properties',
            },
            {
              '@type': 'ListItem',
              'position': 3,
              'name': property.title,
              'item': getCanonicalUrl(),
            },
          ],
        })}
      </script>

      {/* Breadcrumb Navigation with SEO-friendly structure */}
      <nav className="property-breadcrumb" aria-label="Breadcrumb" itemScope itemType="https://schema.org/BreadcrumbList">
        <div className="wps-container">
          <span itemProp="itemListElement" itemScope itemType="https://schema.org/ListItem">
            <button type="button" onClick={onBack} className="breadcrumb-link" itemProp="item">
              <span itemProp="name">Home</span>
            </button>
            <meta itemProp="position" content="1" />
          </span>
          <span className="breadcrumb-separator" aria-hidden="true">›</span>
          <span itemProp="itemListElement" itemScope itemType="https://schema.org/ListItem">
            <button type="button" onClick={onBack} className="breadcrumb-link" itemProp="item">
              <span itemProp="name">Properties</span>
            </button>
            <meta itemProp="position" content="2" />
          </span>
          <span className="breadcrumb-separator" aria-hidden="true">›</span>
          <span itemProp="itemListElement" itemScope itemType="https://schema.org/ListItem">
            <span className="breadcrumb-current" itemProp="name">{property.title}</span>
            <meta itemProp="position" content="3" />
          </span>
        </div>
      </nav>

      <div className="wps-container">
        <div className="property-single-layout">
          <div className="property-single-main">
            <div className="property-gallery">
              <div className="gallery-main">
                <img
                  src={galleryImages[mainImage]}
                  alt={`${property.title} - View ${mainImage + 1} of ${galleryImages.length}`}
                  className={`gallery-main-image ${isFullscreen ? 'gallery-main-image-fullscreen' : ''}`}
                  itemProp="image"
                  onClick={() => isFullscreen && setIsFullscreen(false)}
                  loading={mainImage === 0 ? 'eager' : 'lazy'}
                  width="800"
                  height="600"
                />
                {!isFullscreen && (
                  <>
                    <div className="gallery-badge">{statusLabel}</div>
                    <div className="gallery-actions">
                      <button className="gallery-action-btn" title="Add to favorites" onClick={toggleFavorite}>
                        <i className={`${isFavorite ? 'fas' : 'far'} fa-heart ${isFavorite ? 'favorite-icon-active' : ''}`}></i>
                      </button>
                      <button className="gallery-action-btn" title="Fullscreen" onClick={() => setIsFullscreen(true)}>
                        <i className="fas fa-expand-arrows-alt"></i>
                      </button>
                    </div>
                    <div className="gallery-counter">{mainImage + 1} / {galleryImages.length}</div>
                  </>
                )}
                {isFullscreen && (
                  <div className="gallery-fullscreen-controls">
                    <button className="gallery-fullscreen-btn" onClick={() => setMainImage(Math.max(0, mainImage - 1))}>
                      <i className="fas fa-chevron-left"></i>
                    </button>
                    <span className="gallery-fullscreen-count">{mainImage + 1} / {galleryImages.length}</span>
                    <button className="gallery-fullscreen-btn" onClick={() => setMainImage(Math.min(galleryImages.length - 1, mainImage + 1))}>
                      <i className="fas fa-chevron-right"></i>
                    </button>
                    <button className="gallery-fullscreen-btn" onClick={() => setIsFullscreen(false)}>
                      <i className="fas fa-times"></i>
                    </button>
                  </div>
                )}
              </div>
              {!isFullscreen && (
                <div className="gallery-thumbnails">
                  <button
                    className="gallery-nav gallery-nav-prev"
                    onClick={() => setMainImage(Math.max(0, mainImage - 1))}
                  >
                    <i className="fas fa-chevron-left"></i>
                  </button>
                  {galleryImages.map((img, index) => (
                    <div
                      key={index}
                      className={`gallery-thumb ${index === mainImage ? 'active' : ''}`}
                      onClick={() => setMainImage(index)}
                      role="button"
                      tabIndex={0}
                      aria-label={`View image ${index + 1} of ${galleryImages.length}`}
                    >
                      <img src={img} alt={`Thumbnail ${index + 1}: ${property.title}`} loading="lazy" />
                    </div>
                  ))}
                  <button
                    className="gallery-nav gallery-nav-next"
                    onClick={() => setMainImage(Math.min(galleryImages.length - 1, mainImage + 1))}
                  >
                    <i className="fas fa-chevron-right"></i>
                  </button>
                </div>
              )}
            </div>

            <nav className="property-tabs" aria-label="Property details">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  className={`property-tab ${activeTab === tab.id ? 'active' : ''}`}
                  onClick={() => setActiveTab(tab.id)}
                  aria-pressed={activeTab === tab.id}
                >
                  {tab.label}
                </button>
              ))}
            </nav>

            {activeTab === 'overview' && (
              <section className="property-overview-section" aria-labelledby="property-overview-heading">
                <div className="overview-one-column">
                  <div className="overview-description">
                    <h2 id="property-overview-heading">About This Property</h2>
                    {property.content ? (
                      <div itemProp="description" dangerouslySetInnerHTML={{ __html: property.content }} />
                    ) : (
                      <p><em>No description available for this property.</em></p>
                    )}
                  </div>                 
                </div>
                {renderBottomBar()}
              </section>
            )}

            {activeTab === 'features' && (
              <section className="property-overview-section" aria-labelledby="property-features-heading">
                <h2 id="property-features-heading" className="section-heading">Property Features & Details</h2>
                <dl className="features-grid">
                  {[
                    { icon: 'fa-bed', label: 'Bedrooms', value: property.bedrooms || '0' },
                    { icon: 'fa-bath', label: 'Bathrooms', value: property.bathrooms || '0' },
                    { icon: 'fa-vector-square', label: 'Area', value: property.area ? `${property.area} ` : 'N/A' },
                    { icon: 'fa-building', label: 'Property Type', value: property.property_type || 'N/A' },
                    { icon: 'fa-layer-group', label: 'Floor', value: property.floor || 'N/A' },
                    { icon: 'fa-map-marker-alt', label: 'City', value: property.city || 'N/A' },
                    { icon: 'fa-globe', label: 'Country', value: property.country || 'N/A' },
                  ].filter(f => f.value !== 'N/A' && f.value !== '0' || ['Bedrooms', 'Bathrooms'].includes(f.label)).map((f, i) => (
                    <div key={i} className="feature-detail-card">
                      <i className={`fas ${f.icon} feature-detail-icon`}></i>
                      <div>
                        <dt className="feature-detail-label">{f.label}</dt>
                        <dd className="feature-detail-value">{f.value}</dd>
                      </div>
                    </div>
                  ))}
                  {Object.keys(property)
                    .filter(key => !FEATURE_EXCLUDE_FIELDS.includes(key) && property[key] && property[key] !== 'N/A' && property[key] !== '')
                    .map((slug) => {
                      const label = slug.replace(/property-/g, '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                      const rawValue = property[slug];
                      const displayValue = typeof rawValue === 'object'
                        ? rawValue.name || rawValue.title || JSON.stringify(rawValue)
                        : rawValue;

                      return (
                        <div key={slug} className="feature-detail-card">
                          <i className="fas fa-tag feature-detail-icon"></i>
                          <div>
                            <dt className="feature-detail-label">{label}</dt>
                            <dd className="feature-detail-value">{displayValue}</dd>
                          </div>
                        </div>
                      );
                    })}
                </dl>
                {renderBottomBar()}
              </section>
            )}

            {activeTab === 'location' && (
              <section className="property-overview-section" aria-labelledby="property-location-heading">
                <h2 id="property-location-heading" className="section-heading">Property Location</h2>
                {renderLocationContent({ compact: true })}

                <dl className="location-detail-list">
                  {[
                    { label: 'Address', value: property.address },
                    { label: 'City', value: property.city },
                    { label: 'State', value: property.state },
                    { label: 'Zip Code', value: property.zipcode },
                    { label: 'Country', value: property.country },
                    { label: 'Latitude', value: hasMapCoordinates ? latitude.toFixed(6) : '' },
                    { label: 'Longitude', value: hasMapCoordinates ? longitude.toFixed(6) : '' },
                  ].filter(row => row.value).map(row => (
                    <div key={row.label} className="info-row location-info-row">
                      <dt className="info-label">{row.label}</dt>
                      <dd className="info-value">{row.value}</dd>
                    </div>
                  ))}
                </dl>
                {renderBottomBar()}
              </section>
            )}

            {activeTab === 'additional' && (
              <section className="property-overview-section" aria-labelledby="property-additional-heading">
                <h2 id="property-additional-heading" className="section-heading">Additional Details</h2>
                {additionalDetails.length === 0 ? (
                  <p><em>No additional details available for this property.</em></p>
                ) : (
                  <dl className="additional-details-grid">
                    {additionalDetails.map((item, idx) => {
                      if (item === null || item === undefined) return null;
                      if (typeof item === 'object') {
                        const label = item.label || item.name || item.title || item.key || `Detail ${idx + 1}`;
                        const value = item.value || item.text || item.phone || item.email || item.description || JSON.stringify(item);
                        return (
                          <div key={idx} className="additional-detail-card">
                            <dt className="feature-detail-label">{label}</dt>
                            <dd className="feature-detail-value additional-detail-value">{value}</dd>
                          </div>
                        );
                      }
                      return (
                        <div key={idx} className="additional-detail-card">
                          <dt className="feature-detail-label">{`Detail ${idx + 1}`}</dt>
                          <dd className="feature-detail-value additional-detail-value">{String(item)}</dd>
                        </div>
                      );
                    })}
                  </dl>
                )}
                {renderBottomBar()}
              </section>
            )}

            {activeTab === 'faq' && (
              <section className="property-overview-section" aria-labelledby="property-faq-heading">
                <h2 id="property-faq-heading" className="section-heading">Frequently Asked Questions</h2>
                <div className="property-faq-list">
                  {faqItems.map((item, idx) => (
                    <details className="property-faq-item" key={idx} open={idx === 0}>
                      <summary>{item.question}</summary>
                      <p>{item.answer}</p>
                    </details>
                  ))}
                </div>
                {renderBottomBar()}
              </section>
            )}
          </div>

          <div className="property-single-sidebar" role="complementary" aria-label="Property summary and inquiry">
            <div className="property-details-card">
              <div className="details-header">
                {property.featured && <span className="featured-label">{settings?.featuredLabel || 'FEATURED PROPERTY'}</span>}
                <h1 className="property-title" itemProp="name">{property.title}</h1>
                <p className="property-address" itemProp="address">
                  <i className="fas fa-map-marker-alt" aria-hidden="true"></i>
                  <span>{property.address}</span>
                </p>
                <div className="property-price-large" itemProp="offers" itemScope itemType="https://schema.org/Offer">
                  <span className="price" itemProp="price">{property.price}</span>
                  <meta itemProp="priceCurrency" content="USD" />
                  <span className="status-badge">{statusLabel}</span>
                </div>
              </div>
              
              <div className="property-info-list">
                <h4 className="property-title">Location Detail</h4>                  
                  {property.city && (
                    <div className="info-row">
                      <dt className="info-label"><i className="fas fa-city" aria-hidden="true"></i> City:</dt>
                      <dd className="info-value" itemProp="addressLocality">{property.city}</dd>
                    </div>
                  )}
                  {property.state && (
                    <div className="info-row">
                      <dt className="info-label"><i className="fas fa-map-marked-alt" aria-hidden="true"></i> State:</dt>
                      <dd className="info-value" itemProp="addressRegion">{property.state}</dd>
                    </div>
                  )}
                  {property.zipcode && (
                    <div className="info-row">
                      <dt className="info-label"><i className="fas fa-mail-bulk" aria-hidden="true"></i> Zip Code:</dt>
                      <dd className="info-value" itemProp="postalCode">{property.zipcode}</dd>
                    </div>
                  )}
                  {property.country && (
                    <div className="info-row">
                      <dt className="info-label"><i className="fas fa-globe" aria-hidden="true"></i> Country:</dt>
                      <dd className="info-value" itemProp="addressCountry">{property.country}</dd>
                    </div>
                  )}               
              </div>
            </div>

            <div className="agent-card">
              <h2>Agent Details</h2>
              <div className="agent-details">
                {activeAgent.photo ? (
                  <img src={activeAgent.photo} alt={activeAgent.name} className="agent-photo" loading="lazy" />
                ) : (
                  <div className="agent-photo agent-photo-placeholder" aria-hidden="true">
                    <i className="fas fa-user-tie"></i>
                  </div>
                )}
                <div className="agent-info">
                  <h3 className="agent-name">{activeAgent.name}</h3>
                  <p className="agent-role">{activeAgent.role}</p>
                  {activeAgent.phone && (
                    <a href={`tel:${activeAgent.phone.replace(/[^+\d]/g, '')}`} className="agent-link">
                      <i className="fas fa-phone-flip"></i>
                      <span>{activeAgent.phone}</span>
                    </a>
                  )}
                  {activeAgent.email && (
                    <a href={`mailto:${activeAgent.email}`} className="agent-link">
                      <i className="fas fa-envelope"></i>
                      <span>{activeAgent.email}</span>
                    </a>
                  )}
                </div>
              </div>
            </div>

            <div className="contact-form-card" ref={contactFormRef}>
              <h2>{settings?.contactFormHeading || 'Get More Details'}</h2>
              <p className="form-subtitle">{settings?.contactFormSubtitle || 'Schedule a tour or request more information about this property.'}</p>

              {submitSuccess && (
                <div className="form-alert form-alert-success" role="status">
                  ✓ Thank you! Your inquiry has been sent. We'll be in touch shortly.
                </div>
              )}

              <form onSubmit={handleSubmit}>
                <div className="form-group">
                  <input
                    type="text"
                    name="name"
                    placeholder="Your Name*"
                    value={formData.name}
                    onChange={handleInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <input
                    type="email"
                    name="email"
                    placeholder="Your Email*"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <input
                    type="tel"
                    name="phone"
                    placeholder="Your Phone Number*"
                    value={formData.phone}
                    onChange={handleInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <textarea
                    name="message"
                    placeholder="Tell us what you would like to know about this property..."
                    value={formData.message}
                    onChange={handleInputChange}
                    rows="4"
                  ></textarea>
                </div>

                {submitError && (
                  <div className="form-alert form-alert-error" role="alert">
                    ⚠ {submitError}
                  </div>
                )}

                <button type="submit" className="btn-submit-form" disabled={isSubmitting}>
                  {isSubmitting ? 'Sending...' : 'Request Information'}
                </button>
                <p className="form-privacy">
                  <i className="fas fa-lock"></i> Your information is safe with us. We don't share your details with third parties.
                </p>
              </form>
            </div>

            <div className="property-actions">
              {copyToast && (
                <div className="property-copy-toast" role="status" aria-live="polite">
                  <i className="fas fa-check"></i>
                  <span>{copyToast}</span>
                </div>
              )}
              <button className="action-btn" onClick={toggleFavorite}>
                <span className="action-icon">
                  <i className={`${isFavorite ? 'fas' : 'far'} fa-heart ${isFavorite ? 'favorite-icon-active' : ''}`}></i>
                </span>
                {isFavorite ? 'Saved to Favorites' : 'Add to Favorites'}
              </button>
              <button className="action-btn" onClick={handleShare}>
                <span className="action-icon"><i className="fas fa-share-alt"></i></span>
                Share Property
              </button>
              <div className="social-share">
                <a href={`https://www.facebook.com/sharer/sharer.php?u=${getPageUrl()}`} target="_blank" rel="noopener noreferrer" className="social-icon" title="Share on Facebook"><i className="fab fa-facebook-f"></i></a>
                <a href={`https://twitter.com/intent/tweet?url=${getPageUrl()}&text=${getPageTitle()}`} target="_blank" rel="noopener noreferrer" className="social-icon" title="Share on Twitter"><i className="fab fa-twitter"></i></a>
                <a href={`https://www.linkedin.com/shareArticle?mini=true&url=${getPageUrl()}&title=${getPageTitle()}`} target="_blank" rel="noopener noreferrer" className="social-icon" title="Share on LinkedIn"><i className="fab fa-linkedin-in"></i></a>
                <a href={`mailto:?subject=${getPageTitle()}&body=${getPageUrl()}`} className="social-icon" title="Share via Email"><i className="fas fa-envelope"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>

      {!isFullscreen && (
        <button
          type="button"
          className="mobile-back-to-search"
          onClick={onBack}
          aria-label="Back to property search"
        >
          <i className="fas fa-arrow-left" aria-hidden="true"></i>
          <span>Back</span>
        </button>
      )}
    </article>
  );
}

export default PropertySingle;

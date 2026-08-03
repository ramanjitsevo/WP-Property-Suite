import React, { useState, useEffect } from 'react';
import { APIProvider, useMapsLibrary } from '@vis.gl/react-google-maps';
import './App.css';
import PropertySingle from './PropertySingle';
import LeadFormModal from './LeadFormModal';

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

const getPropertyShareUrl = (property) => {
  if (typeof window === 'undefined') return '';

  const url = new URL(window.location.href);
  url.searchParams.delete('property');
  url.searchParams.set('wps_property', getPropertySlug(property));
  return url.toString();
};

function LocationAutocompleteInput({ value, onChange, placeholder = 'Enter location...' }) {
  const places = useMapsLibrary('places');
  const [inputValue, setInputValue] = useState(value || '');
  const [suggestions, setSuggestions] = useState([]);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setInputValue(value || '');
  }, [value]);

  useEffect(() => {
    if (!places?.AutocompleteSuggestion || inputValue.trim().length < 2) {
      setSuggestions([]);
      return undefined;
    }

    let isActive = true;
    const timeoutId = window.setTimeout(async () => {
      try {
        const { suggestions: nextSuggestions = [] } =
          await places.AutocompleteSuggestion.fetchAutocompleteSuggestions({
            input: inputValue,
          });

        if (isActive) {
          setSuggestions(nextSuggestions);
          setIsOpen(nextSuggestions.length > 0);
        }
      } catch {
        if (isActive) {
          setSuggestions([]);
          setIsOpen(false);
        }
      }
    }, 250);

    return () => {
      isActive = false;
      window.clearTimeout(timeoutId);
    };
  }, [inputValue, places]);

  const handleInputChange = (event) => {
    const nextValue = event.target.value;
    setInputValue(nextValue);
    onChange(nextValue);
  };

  const handleSelectSuggestion = async (suggestion) => {
    const prediction = suggestion.placePrediction;

    if (!prediction) {
      return;
    }

    const place = prediction.toPlace();
    await place.fetchFields({ fields: ['displayName', 'formattedAddress'] });

    const nextValue = place.formattedAddress || place.displayName || prediction.text?.text || '';
    setInputValue(nextValue);
    onChange(nextValue);
    setSuggestions([]);
    setIsOpen(false);
  };

  return (
    <div className="wps-location-autocomplete">
      <input
        type="text"
        placeholder={placeholder}
        value={inputValue}
        onChange={handleInputChange}
        onFocus={() => setIsOpen(suggestions.length > 0)}
        autoComplete="off"
      />
      {isOpen && suggestions.length > 0 && (
        <div className="wps-location-suggestions">
          {suggestions.map((suggestion) => {
            const prediction = suggestion.placePrediction;
            const suggestionText = prediction?.text?.text || '';

            if (!suggestionText) {
              return null;
            }

            return (
              <button
                type="button"
                key={prediction.placeId || suggestionText}
                className="wps-location-suggestion"
                onMouseDown={(event) => event.preventDefault()}
                onClick={() => handleSelectSuggestion(suggestion)}
              >
                <i className="fas fa-map-marker-alt"></i>
                <span>{suggestionText}</span>
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}

function App({ containerId }) {
  const initialProperties = typeof window !== 'undefined' && Array.isArray(window.propertyPluginData?.initialProperties)
    ? window.propertyPluginData.initialProperties
    : [];

  const [properties, setProperties] = useState(initialProperties);
  const [loading, setLoading] = useState(initialProperties.length === 0);
  const [error, setError] = useState(null);
  const [selectedProperty, setSelectedProperty] = useState(null);
  const [showLeadForm, setShowLeadForm] = useState(false);
  const [leadProperty, setLeadProperty] = useState(null);
  const [showFavoritesOnly, setShowFavoritesOnly] = useState(false);
  const [searching, setSearching] = useState(false);
  const [, setFavoritesVersion] = useState(0);

  // Get settings from WordPress
  const settings = window.propertyPluginData?.settings || {};
  const googlePlacesApiKey = (settings.googleApiKey || '').trim();
  const hasGooglePlacesKey = Boolean(googlePlacesApiKey);
  const desktopBannerHeight = parseInt(settings.bannerHeight, 10) || 320;
  const mobileBannerHeight = parseInt(settings.bannerHeightMobile, 10) || 250;
  const baseFontSize = parseInt(settings.fontSize, 10) || 16;
  console.log('Google Places API Key:', googlePlacesApiKey);

  // Filter states
  const [filters, setFilters] = useState({
    status: 'all',
    keyword: '',
    location: '',
    propertyType: 'all',
    minPrice: '',
    maxPrice: '',
    bedrooms: 'any',
    bathrooms: 'any',
    sortBy: 'latest'
  });

  // Pagination states
  const [currentPage, setCurrentPage] = useState(1);
  const postsPerPage = parseInt(settings.propertiesPerPage, 10) || 12;

  // Check if user has already submitted the lead form
  const hasSubmittedLeadForm = localStorage.getItem('propertyLeadFormSubmitted') === 'true';

  // Favorites helpers — must be defined before getFilteredProperties
  const getFavoriteIds = () => {
    try {
      const raw = localStorage.getItem('property_favorites');
      const ids = JSON.parse(raw || '[]');
      return Array.isArray(ids) ? ids.map(Number) : [];
    } catch {
      return [];
    }
  };

  const favoritesCount = getFavoriteIds().length;

  const toggleFavoritesView = () => {
    setShowFavoritesOnly(prev => !prev);
    setCurrentPage(1);
  };

  const isPropertyFavorite = (propertyId) => {
    return getFavoriteIds().includes(Number(propertyId));
  };

  const togglePropertyFavorite = (event, propertyId) => {
    event.preventDefault();
    event.stopPropagation();

    const favoriteIds = getFavoriteIds();
    const numericId = Number(propertyId);
    const updated = favoriteIds.includes(numericId)
      ? favoriteIds.filter(id => id !== numericId)
      : [...favoriteIds, numericId];

    localStorage.setItem('property_favorites', JSON.stringify(updated));
    setFavoritesVersion(version => version + 1);
    setCurrentPage(1);
  };

  useEffect(() => {
    fetchProperties();
  }, []);

  useEffect(() => {
    if (!properties.length || selectedProperty || typeof window === 'undefined') return;

    const searchParams = new URLSearchParams(window.location.search);
    const propertyParam = searchParams.get('wps_property') || searchParams.get('property');
    if (!propertyParam) return;

    const idMatch = propertyParam.match(/-(\d+)$/);
    const propertyFromUrl = properties.find((property) => {
      if (idMatch && Number(property.id) === Number(idMatch[1])) return true;
      return getPropertySlug(property) === propertyParam;
    });

    if (propertyFromUrl) {
      localStorage.setItem('propertyLeadFormSubmitted', 'true');
      setSelectedProperty(propertyFromUrl);
      window.scrollTo(0, 0);
    }
  }, [properties, selectedProperty]);

  const fetchProperties = async () => {
    try {
      setLoading(true);
      const apiUrl = window.propertyPluginData?.apiUrl || '/wp-json/wps/v1';

      const response = await fetch(`${apiUrl}/properties`, {
        headers: {
          'X-WP-Nonce': window.propertyPluginData?.nonce || '',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to fetch properties');
      }

      const data = await response.json();
      setProperties(data);
      setError(null);
    } catch (err) {
      if (initialProperties.length === 0) {
        setError(err.message);
      }
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusMap = {
      'for-sale': { label: 'For Sale', color: '#4caf50' },
      'for-rent': { label: 'For Rent', color: '#2196f3' },
      'sold': { label: 'Sold', color: '#f44336' },
      'rented': { label: 'Rented', color: '#ff9800' },
    };
    return statusMap[status] || { label: 'For Sale', color: '#4caf50' };
  };

  const handlePropertyClick = (property) => {
    // If user has already submitted the form, go directly to property page
    if (hasSubmittedLeadForm) {
      setSelectedProperty(property);
      window.history.pushState(null, '', getPropertyShareUrl(property));
      window.scrollTo(0, 0);
    } else {
      // Otherwise show the lead form
      setLeadProperty(property);
      setShowLeadForm(true);
    }
  };

  const handleLeadFormClose = () => {
    setShowLeadForm(false);
    setLeadProperty(null);
  };

  const handleLeadFormSubmit = () => {
    // Save to localStorage that user has submitted the form
    localStorage.setItem('propertyLeadFormSubmitted', 'true');

    // Close the modal and open the property page
    setShowLeadForm(false);
    setSelectedProperty(leadProperty);
    window.history.pushState(null, '', getPropertyShareUrl(leadProperty));
    setLeadProperty(null);
    window.scrollTo(0, 0);
  };

  const handleBackToList = () => {
    setSelectedProperty(null);
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href);
      url.searchParams.delete('wps_property');
      url.searchParams.delete('property');
      window.history.pushState(null, '', url.toString());
    }
  };

  // Get unique property types from the properties data
  const getUniquePropertyTypes = () => {
    const types = new Set();
    properties.forEach(property => {
      // Check property_type field from WordPress API (primary field)
      let propertyType = property.property_type || property.type || '';

      if (propertyType && propertyType !== 'Property') {
        // Capitalize first letter of each word
        const formattedType = propertyType
          .toString()
          .toLowerCase()
          .split(/[\s,]+/) // Split by space or comma
          .map(word => word.trim())
          .filter(word => word.length > 0)
          .map(word => word.charAt(0).toUpperCase() + word.slice(1))
          .join(', ');

        if (formattedType) {
          types.add(formattedType);
        }
      }
    });
    return Array.from(types);
  };

  const uniquePropertyTypes = getUniquePropertyTypes();

  // Filter handler functions
  const handleFilterChange = (filterName, value) => {
    setFilters(prev => ({
      ...prev,
      [filterName]: value
    }));
  };

  const handleStatusFilter = (status) => {
    setFilters(prev => ({
      ...prev,
      status: status
    }));
  };

  const handleApplyFilters = () => {
    setSearching(true);
    setCurrentPage(1);
    setTimeout(() => setSearching(false), 400);
  };

  const handleResetFilters = () => {
    setFilters({
      status: 'all',
      keyword: '',
      location: '',
      propertyType: 'all',
      minPrice: '',
      maxPrice: '',
      bedrooms: 'any',
      bathrooms: 'any',
      sortBy: 'latest'
    });
    setCurrentPage(1);
  };

  const handleSearch = () => {
    setSearching(true);
    setCurrentPage(1);
    setTimeout(() => setSearching(false), 400);
  };

  // Parse price string to number
  const parsePrice = (priceString) => {
    if (!priceString) return 0;
    const cleanPrice = priceString.replace(/[^0-9]/g, '');
    return parseFloat(cleanPrice) || 0;
  };

  // Get filtered and sorted properties
  const getFilteredProperties = () => {
    let filtered = [...properties];

    if (showFavoritesOnly) {
      const favIds = getFavoriteIds();
      filtered = filtered.filter(p => favIds.includes(Number(p.id)));
    }


    // Filter by status
    if (filters.status !== 'all') {
      filtered = filtered.filter(p => p.status === filters.status);
    }

    // Filter by keyword
    if (filters.keyword) {
      const keyword = filters.keyword.toLowerCase();
      filtered = filtered.filter(p =>
        p.title?.toLowerCase().includes(keyword) ||
        p.description?.toLowerCase().includes(keyword)
      );
    }

    // Filter by location
    if (filters.location) {
      const location = filters.location.toLowerCase();
      filtered = filtered.filter(p =>
        p.address?.toLowerCase().includes(location) ||
        p.location?.toLowerCase().includes(location)
      );
    }

    // Filter by property type
    if (filters.propertyType !== 'all') {
      filtered = filtered.filter(p => {
        // Check property_type field from WordPress API
        const propertyType = (p.property_type || p.type || '').toLowerCase().trim();
        const filterType = filters.propertyType.toLowerCase().trim();

        // Remove trailing 's' for plural matching
        const singularPropertyType = propertyType.replace(/s$/, '');
        const singularFilterType = filterType.replace(/s$/, '');

        // Exact match, contains match, or singular/plural match
        return propertyType === filterType ||
          singularPropertyType === singularFilterType ||
          propertyType.includes(filterType) ||
          filterType.includes(propertyType);
      });
    }

    // Filter by price range
    if (filters.minPrice) {
      const minPrice = parseFloat(filters.minPrice);
      filtered = filtered.filter(p => parsePrice(p.price) >= minPrice);
    }

    if (filters.maxPrice) {
      const maxPrice = parseFloat(filters.maxPrice);
      filtered = filtered.filter(p => parsePrice(p.price) <= maxPrice);
    }

    // Filter by bedrooms
    if (filters.bedrooms !== 'any') {
      const bedrooms = parseInt(filters.bedrooms);
      if (filters.bedrooms === '5+') {
        filtered = filtered.filter(p => parseInt(p.bedrooms) >= 5);
      } else {
        filtered = filtered.filter(p => parseInt(p.bedrooms) === bedrooms);
      }
    }

    // Filter by bathrooms
    if (filters.bathrooms !== 'any') {
      const bathrooms = parseInt(filters.bathrooms);
      if (filters.bathrooms === '5+') {
        filtered = filtered.filter(p => parseInt(p.bathrooms) >= 5);
      } else {
        filtered = filtered.filter(p => parseInt(p.bathrooms) === bathrooms);
      }
    }

    // Sort properties
    if (filters.sortBy === 'price-low') {
      filtered.sort((a, b) => parsePrice(a.price) - parsePrice(b.price));
    } else if (filters.sortBy === 'price-high') {
      filtered.sort((a, b) => parsePrice(b.price) - parsePrice(a.price));
    }

    return filtered;
  };

  const filteredProperties = getFilteredProperties();

  // Pagination logic
  const totalPages = Math.ceil(filteredProperties.length / postsPerPage);
  const indexOfLastPost = currentPage * postsPerPage;
  const indexOfFirstPost = indexOfLastPost - postsPerPage;
  const currentPosts = filteredProperties.slice(indexOfFirstPost, indexOfLastPost);

  // Reset to page 1 when filters change
  useEffect(() => {
    setCurrentPage(1);
  }, [filters]);

  const handlePageChange = (pageNumber) => {
    setCurrentPage(pageNumber);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };


  if (loading) {
    return <div className="wps-loading">Loading properties...</div>;
  }

  if (error) {
    return <div className="wps-error">Error: {error}</div>;
  }

  // Show single property page if a property is selected
  if (selectedProperty) {
    const singlePropertyContent = (
      <PropertySingle property={selectedProperty} onBack={handleBackToList} settings={settings} />
    );

    if (hasGooglePlacesKey) {
      return (
        <APIProvider apiKey={googlePlacesApiKey} libraries={['places']}>
          {singlePropertyContent}
        </APIProvider>
      );
    }

    return singlePropertyContent;
  }

  // Build the full app content; wrap in a single APIProvider when the key
  // is available so the Google Maps JS API is loaded exactly once.
  const appContent = (
    <div
      className="wps-app"
      id={containerId}
      style={{
        '--wps-primary-color': settings.primaryColor || '#2563eb',
        '--wps-secondary-color': settings.secondaryColor || '#10b981',
        '--wps-text-color': settings.textColor || '#1f2937',
        '--wps-background-color': settings.backgroundColor || '#f3f4f6',
        '--wps-card-background': settings.cardBackground || '#ffffff',
        '--wps-font-family': settings.fontFamily || 'Arial, sans-serif',
        '--wps-font-size': `${baseFontSize}px`,
        '--wps-banner-height': `${desktopBannerHeight}px`,
        '--wps-banner-mobile-height': `${mobileBannerHeight}px`
      }}
    >
      {/* Apply custom CSS from settings */}
      {settings.customCSS && (
        <style dangerouslySetInnerHTML={{ __html: settings.customCSS }} />
      )}     

      {/* Lead Form Modal */}
      {showLeadForm && leadProperty && (
        <LeadFormModal
          property={leadProperty}
          onClose={handleLeadFormClose}
          onSubmit={handleLeadFormSubmit}
        />
      )}

      {/* Hero Section */}
      <section
        className="hero-section"
        style={{
          backgroundImage: settings.bannerImage ? `url(${settings.bannerImage})` : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          position: 'relative'
        }}
      >
        <div
          className="hero-overlay"
          style={{
            backgroundColor: settings.bannerOverlayColor || '#000000',
            opacity: (settings.bannerOverlay || 50) / 100
          }}
        ></div>
        <div className="hero-content">
          <h1 className="hero-title">{settings.headerText || 'Discover Your Perfect Property'}</h1>
          {settings.bannerSubtitle && <p className="hero-subtitle">{settings.bannerSubtitle}</p>}
        </div>
      </section>

      {/* Search Bar Section */}
      <section className="search-section">
        <div className="search-container">
          <div className="search-tabs">
            <button
              className={`search-tab ${filters.status === 'all' ? 'active' : ''}`}
              onClick={() => handleStatusFilter('all')}
            >
              All Status
            </button>
            <button
              className={`search-tab ${filters.status === 'for-sale' ? 'active' : ''}`}
              onClick={() => handleStatusFilter('for-sale')}
            >
              For Sale
            </button>
            <button
              className={`search-tab ${filters.status === 'for-rent' ? 'active' : ''}`}
              onClick={() => handleStatusFilter('for-rent')}
            >
              For Rent
            </button>
            <button
              className={`search-tab ${filters.status === 'new-launch' ? 'active' : ''}`}
              onClick={() => handleStatusFilter('new-launch')}
            >
              New Launch
            </button>
          </div>
          <div className="search-form">
            <div className="search-field">
              <label>Keyword</label>
              <input
                type="text"
                placeholder="Enter keyword..."
                value={filters.keyword}
                onChange={(e) => handleFilterChange('keyword', e.target.value)}
              />
            </div>
            <div className="search-field">
              <label>Location</label>
              {hasGooglePlacesKey ? (
                <LocationAutocompleteInput
                  value={filters.location}
                  onChange={(nextValue) => handleFilterChange('location', nextValue)}
                />
              ) : (
                <input
                  type="text"
                  placeholder="Enter location..."
                  value={filters.location}
                  onChange={(e) => handleFilterChange('location', e.target.value)}
                />
              )}
            </div>
            <div className="search-field">
              <label>Property Type</label>
              <select
                value={filters.propertyType}
                onChange={(e) => handleFilterChange('propertyType', e.target.value)}
              >
                <option value="all">All Types</option>
                {uniquePropertyTypes.map(type => (
                  <option key={type} value={type.toLowerCase()}>
                    {type}
                  </option>
                ))}
              </select>
            </div>
            <button className="btn-search" onClick={handleSearch}>Search Properties</button>
          </div>
          
        </div>
      </section>

      {/* Properties Listing Section */}
      <section className="properties-section">
        <div className="properties-container">
          <div className="properties-layout">
            {/* Sidebar Filters */}
            {settings.enableFilters !== '0' && (
              <aside
                className="properties-sidebar"
                style={{
                  order: settings.sidebarPosition === 'right' ? '2' : '1',
                  width: `${settings.sidebarWidth || 280}px`
                }}
              >
                <div className="filter-header">
                  <h3>Filter Properties</h3>
                  <button className="btn-reset" onClick={handleResetFilters}>Reset</button>
                </div>

                <div className="filter-group">
                  <h4>Location</h4>
                  {hasGooglePlacesKey ? (
                    <LocationAutocompleteInput
                      value={filters.location}
                      onChange={(nextValue) => handleFilterChange('location', nextValue)}
                    />
                  ) : (
                    <input
                      type="text"
                      placeholder="Enter location..."
                      value={filters.location}
                      onChange={(e) => handleFilterChange('location', e.target.value)}
                    />
                  )}
                </div>

                <div className="filter-group">
                  <h4>Property Type</h4>
                  <div className="filter-options">
                    <label>
                      <input
                        type="radio"
                        name="propertyType"
                        checked={filters.propertyType === 'all'}
                        onChange={() => handleFilterChange('propertyType', 'all')}
                      /> All Types
                    </label>
                    {uniquePropertyTypes.map(type => (
                      <label key={type}>
                        <input
                          type="radio"
                          name="propertyType"
                          checked={filters.propertyType === type.toLowerCase()}
                          onChange={() => handleFilterChange('propertyType', type.toLowerCase())}
                        /> {type}
                      </label>
                    ))}
                  </div>
                </div>

                <div className="filter-group">
                  <h4>Price Range</h4>
                  <div className="price-range-inputs">
                    <input
                      type="number"
                      placeholder="Min Price"
                      value={filters.minPrice}
                      onChange={(e) => handleFilterChange('minPrice', e.target.value)}
                      style={{ width: '48%', padding: '8px', marginBottom: '10px' }}
                    />
                    <input
                      type="number"
                      placeholder="Max Price"
                      value={filters.maxPrice}
                      onChange={(e) => handleFilterChange('maxPrice', e.target.value)}
                      style={{ width: '48%', padding: '8px', marginBottom: '10px' }}
                    />
                  </div>
                  <div className="price-range">
                    <span>$10,000</span>
                    <input
                      type="range"
                      className="price-slider"
                      min="10000"
                      max="5000000"
                      step="10000"
                      value={filters.maxPrice || 2500000}
                      onChange={(e) => handleFilterChange('maxPrice', e.target.value)}
                    />
                    <span>$5,000,000</span>
                  </div>
                </div>

                <div className="filter-group">
                  <h4>Bedrooms</h4>
                  <div className="number-options">
                    <button
                      className={`number-btn ${filters.bedrooms === 'any' ? 'active' : ''}`}
                      onClick={() => handleFilterChange('bedrooms', 'any')}
                    >
                      Any
                    </button>
                    {[1, 2, '3+'].map(num => (
                      <button
                        key={num}
                        className={`number-btn ${filters.bedrooms === num.toString() ? 'active' : ''}`}
                        onClick={() => handleFilterChange('bedrooms', num.toString())}
                      >
                        {num}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="filter-group">
                  <h4>Bathrooms</h4>
                  <div className="number-options">
                    <button
                      className={`number-btn ${filters.bathrooms === 'any' ? 'active' : ''}`}
                      onClick={() => handleFilterChange('bathrooms', 'any')}
                    >
                      Any
                    </button>
                    {[1, 2, '3+'].map(num => (
                      <button
                        key={num}
                        className={`number-btn ${filters.bathrooms === num.toString() ? 'active' : ''}`}
                        onClick={() => handleFilterChange('bathrooms', num.toString())}
                      >
                        {num}
                      </button>
                    ))}
                  </div>
                </div>

                <button className="btn-apply-filters" onClick={handleApplyFilters}>Apply Filters</button>
              </aside>
            )}

            {/* Main Content */}
            <div
              className="properties-main"
              style={{
                order: settings.sidebarPosition === 'right' ? '1' : '2',
                flex: settings.enableFilters !== '0' ? '1' : 'none',
                width: settings.enableFilters === '0' ? '100%' : 'auto'
              }}
            >
              <div className="properties-header">
                <div>
                  <p className="results-count">
                    {showFavoritesOnly
                      ? `Showing ${indexOfFirstPost + 1}-${Math.min(indexOfLastPost, filteredProperties.length)} of ${filteredProperties.length} Saved Properties`
                      : `Showing ${indexOfFirstPost + 1}-${Math.min(indexOfLastPost, filteredProperties.length)} of ${filteredProperties.length} Properties`}
                  </p>                 
                </div>
                <div className="sort-options">
                  <button
                    className={`btn-favorites-toggle ${showFavoritesOnly ? 'active' : ''}`}
                    onClick={toggleFavoritesView}
                    title={showFavoritesOnly ? 'Show all properties' : 'Show saved properties'}
                  >
                    <i className={`fa-heart ${showFavoritesOnly ? 'fas' : 'far'}`}></i>
                    <span>Favorites</span>
                    {favoritesCount > 0 && (
                      <span className="favorites-badge">{favoritesCount}</span>
                    )}
                  </button>
                  <label>Sort By:</label>
                  <select
                    value={filters.sortBy}
                    onChange={(e) => handleFilterChange('sortBy', e.target.value)}
                  >
                    <option value="latest">Latest</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                  </select>
                </div>
              </div>

              {/* Properties Grid */}
              <div className={`properties-grid${searching ? ' is-searching' : ''}`}>
                {searching && (
                  <div className="wps-search-loader">
                    <div className="wps-search-spinner"></div>
                    <p>Searching properties...</p>
                  </div>
                )}
                {currentPosts.length === 0 ? (
                  <div className="no-properties">
                    {showFavoritesOnly ? (
                      <>
                        <p>You haven't saved any properties yet. Click the ❤ icon on a property to save it.</p>
                        <button className="btn-add-first" onClick={() => setShowFavoritesOnly(false)}>
                          Show All Properties
                        </button>
                      </>
                    ) : (
                      <>
                        <p>No properties found matching your filters. Try adjusting your search criteria.</p>
                        <button className="btn-add-first" onClick={handleResetFilters}>
                          Reset Filters
                        </button>
                      </>
                    )}
                  </div>
                ) : (
                  currentPosts.map((property) => {
                    const statusInfo = getStatusBadge(property.status);
                    const isFavorite = isPropertyFavorite(property.id);
                    return (
                      <div
                        key={property.id}
                        className="property-card"
                        style={{
                          cursor: 'pointer',
                          backgroundColor: 'var(--wps-card-background)'
                        }}
                        onClick={() => handlePropertyClick(property)}
                      >
                        <div className="property-image-container">
                          {(() => {
                            const imgSrc = property.thumbnail
                              || property.thumbnail_url
                              || (property.gallery && property.gallery.length > 0 ? property.gallery[0] : null)
                              || (Array.isArray(property.gallery_urls) && property.gallery_urls.length > 0 ? property.gallery_urls[0] : null);
                            return imgSrc ? (
                              <img
                                src={imgSrc}
                                alt={property.title}
                                className="property-image"
                                style={{
                                  width: '100%',
                                  height: '100%',
                                  display: 'block',
                                  objectFit: 'cover',
                                  objectPosition: 'center center',
                                }}
                              />
                            ) : (
                              <div className="property-image-placeholder">
                                <span>No Image</span>
                              </div>
                            );
                          })()}
                          {settings.showBadge !== '0' && (
                            <div
                              className="property-badge"
                              style={{ backgroundColor: statusInfo.color }}
                            >
                              {statusInfo.label}
                            </div>
                          )}
                          <button
                            type="button"
                            className={`btn-favorite ${isFavorite ? 'active' : ''}`}
                            onClick={(event) => togglePropertyFavorite(event, property.id)}
                            aria-label={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
                            title={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
                          >
                            <i className={`${isFavorite ? 'fas' : 'far'} fa-heart`}></i>
                          </button>
                        </div>
                        <div className="property-details">
                          <h3 className="property-name">{property.title}</h3>
                          {settings.showAddress !== '0' && (
                            <p className="property-address">
                              <i className="fas fa-map-marker-alt"></i> {property.city || property.address}
                            </p>
                          )}
                          <p className="property-price">{property.price}</p>
                          <div className="property-features">
                            {[
                              property.bedrooms && property.bedrooms !== 'N/A'
                                ? { icon: 'fa-bed', label: `${property.bedrooms} Beds` }
                                : null,
                              property.bathrooms && property.bathrooms !== 'N/A'
                                ? { icon: 'fa-bath', label: `${property.bathrooms} Baths` }
                                : null,
                              property.floor && property.floor !== 'N/A'
                                ? { icon: 'fa-building', label: `Floor: ${property.floor}` }
                                : null,
                              settings.showArea !== '0' && property.area && property.area !== 'N/A'
                                ? { icon: 'fa-ruler-combined', label: property.area }
                                : null,
                            ].filter(Boolean).slice(0, 3).map((feature) => (
                              <span key={`${property.id}-${feature.icon}`}>
                                <i className={`fas ${feature.icon}`}></i> {feature.label}
                              </span>
                            ))}
                          </div>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
              {/* Pagination */}
              {totalPages > 1 && (
                <div className="pagination">
                  <button
                    className="page-btn"
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    style={{ opacity: currentPage === 1 ? 0.5 : 1, cursor: currentPage === 1 ? 'not-allowed' : 'pointer' }}
                  >
                    ‹
                  </button>
                  {Array.from({ length: totalPages }, (_, index) => index + 1).map(pageNum => (
                    <button
                      key={pageNum}
                      className={`page-btn ${currentPage === pageNum ? 'active' : ''}`}
                      onClick={() => handlePageChange(pageNum)}
                    >
                      {pageNum}
                    </button>
                  ))}
                  <button
                    className="page-btn"
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === totalPages}
                    style={{ opacity: currentPage === totalPages ? 0.5 : 1, cursor: currentPage === totalPages ? 'not-allowed' : 'pointer' }}
                  >
                    ›
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="cta-section" style={{ backgroundColor: settings.ctaBgColor || '#f0f9ff' }}>
        <div className="cta-container">
          <div className="cta-image">
            {settings.ctaImage ? (
              <img
                src={settings.ctaImage}
                alt={settings.ctaTitle || 'Property CTA'}
              />
            ) : (
              <div className="cta-image-placeholder" aria-hidden="true"></div>
            )}
          </div>
          <div className="cta-content" style={{ color: settings.ctaTextColor || '#1e3a5f' }}>
            <h2>{settings.ctaTitle || 'Want to Sell or Rent Your Property?'}</h2>
            <p>{settings.ctaDescription || 'List your property with us and reach thousands of potential buyers and renters.'}</p>
            <a
              href={settings.ctaButtonUrl || '/wp-admin/post-new.php?post_type=wps_property'}
              className="btn-add-property-large"
            >
              {settings.ctaButtonText || 'Add Property Now'}
            </a>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section
        className="features-section"
        style={{ backgroundColor: settings.featuresBgColor || '#ffffff' }}
      >
        <div className="features-container" style={{ color: settings.featuresTextColor || '#1f2937' }}>
          {(settings.features || [
            { icon: 'fas fa-trophy', title: 'Trusted by Thousands', description: 'Join thousands of happy clients who found their perfect property.' },
            { icon: 'fas fa-chart-bar', title: 'Wide Range of Properties', description: 'Explore a wide range of properties for sale and rent.' },
            { icon: 'fas fa-users', title: 'Expert Agents', description: 'Work with experienced agents to find the best property.' },
            { icon: 'fas fa-shield-alt', title: 'Secure & Easy Process', description: 'Enjoy a secure and hassle-free property buying or renting process.' },
          ]).map((feature, idx) => (
            <div className="feature-item" key={idx}>
              <div className="feature-icon"><i className={feature.icon}></i></div>
              <h4>{feature.title}</h4>
              <p>{feature.description}</p>
            </div>
          ))}
        </div>
      </section>
    </div>
  );

  // Single top-level APIProvider – avoids loading the Google Maps JS API
  // script multiple times, which triggers "API Key not found" errors.
  if (hasGooglePlacesKey) {
    return (
      <APIProvider apiKey={googlePlacesApiKey} libraries={['places']}>
        {appContent}
      </APIProvider>
    );
  }

  return appContent;
}

export default App;

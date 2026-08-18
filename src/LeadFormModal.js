import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import './LeadFormModal.css';

function LeadFormModal({ property, onClose, onSubmit }) {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    message: `I'm interested in ${property?.title || 'this property'}...`
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState('');
  const [submitSuccess, setSubmitSuccess] = useState(false);

  const apiUrl  = window.propertyPluginData?.apiUrl  || '/wp-json/wps/v1';
  const nonce   = window.propertyPluginData?.nonce   || '';

  const handleInputChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitError('');

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
      const response = await fetch(`${apiUrl}/leads`, {
        method:  'POST',
        headers: {
          'Content-Type':  'application/json',
          'X-WP-Nonce':    nonce,
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `Server error (${response.status})`);
      }

      setSubmitSuccess(true);

      // Notify parent and close after a short delay so user sees the success message
      setTimeout(() => {
        onSubmit(formData);
      }, 1200);

    } catch (err) {
      setSubmitError(err.message || 'Something went wrong. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!property || !mounted) return null;

  const modal = (
    <div className="lead-modal-overlay" onClick={onClose}>
      <div className="lead-modal-content" onClick={(e) => e.stopPropagation()}>
        <button className="lead-modal-close" onClick={onClose}>×</button>

        <div className="lead-modal-body">
          {/* Left Side */}
          <div className="lead-modal-left">
            <div className="lead-modal-icon">
              <i class="fas fa-home"></i>
            </div>
            <h3>Interested in this property?</h3>
            <p>Fill out the form and our property expert will get back to you shortly.</p>
            
            <div className="lead-benefits">
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span>Personalized Assistance</span>
              </div>
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span>Best Price Guarantee</span>
              </div>
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span>Expert Property Guidance</span>
              </div>
              <div className="benefit-item">
                <span className="benefit-icon">✓</span>
                <span>100% Secure & Confidential</span>
              </div>
            </div>
          </div>

          {/* Right Side - Form */}
          <div className="lead-modal-right">
            {submitSuccess ? (
              <div className="lead-form-header" style={{ textAlign: 'center', padding: '40px 20px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>✅</div>
                <h3 style={{ color: '#10b981' }}>Thank You!</h3>
                <p>Your enquiry has been submitted successfully.<br />We will contact you shortly.</p>
              </div>
            ) : (
              <>
                <div className="lead-form-header">
                  <h3>Get More Details</h3>
                  <p>Please fill in your details and we will contact you soon.</p>
                </div>

                {submitError && (
                  <div className="lead-form-error" style={{
                    background: '#fef2f2', border: '1px solid #fca5a5',
                    color: '#b91c1c', padding: '10px 14px', borderRadius: '6px',
                    margin: '0 24px', fontSize: '14px'
                  }}>
                    ⚠️ {submitError}
                  </div>
                )}

                <form onSubmit={handleSubmit} className="lead-form">
                  <div className="form-group">
                    <div className="input-icon-wrapper">
                      <i className="input-icon fas fa-user" aria-hidden="true"></i>
                      <input
                        type="text"
                        name="name"
                        placeholder="Your Name*"
                        value={formData.name}
                        onChange={handleInputChange}
                        required
                        disabled={isSubmitting}
                      />
                    </div>
                  </div>

                  <div className="form-group">
                    <div className="input-icon-wrapper">
                      <i className="input-icon fas fa-envelope" aria-hidden="true"></i>
                      <input
                        type="email"
                        name="email"
                        placeholder="Your Email*"
                        value={formData.email}
                        onChange={handleInputChange}
                        required
                        disabled={isSubmitting}
                      />
                    </div>
                  </div>

                  <div className="form-group">
                    <div className="input-icon-wrapper">
                      <i className="input-icon fas fa-phone-flip" aria-hidden="true"></i>
                      <input
                        type="tel"
                        name="phone"
                        placeholder="Your Phone Number*"
                        value={formData.phone}
                        onChange={handleInputChange}
                        required
                        disabled={isSubmitting}
                      />
                    </div>
                  </div>

                  <div className="form-group">
                    <div className="input-icon-wrapper">
                      <i className="input-icon fas fa-comment" aria-hidden="true"></i>
                      <textarea
                        name="message"
                        placeholder="I'm interested in..."
                        value={formData.message}
                        onChange={handleInputChange}
                        rows="3"
                        disabled={isSubmitting}
                      ></textarea>
                    </div>
                  </div>

                  <button
                    type="submit"
                    className="btn-submit-lead"
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? 'Submitting...' : 'Submit Enquiry'}
                  </button>

                  <p className="form-privacy-note">
                    Your information is secure and will never be shared with third parties
                  </p>
                </form>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  return createPortal(modal, document.body);
}

export default LeadFormModal;


• I explored the official FedEx portal starting from https://developer.fedex.com/ and followed only FedEx-hosted links.

  What I was able to verify:

  - The developer portal root redirects to the location selector and then to the U.S. Developer Portal home page.
  - The portal’s public-facing integration pages are accessible and contain useful official guidance.
  - The in-portal API catalog and API-specific docs pages were not readable in this environment; they load as the portal
    shell and then show a timeout/error state.

  - The FedEx.com integration pages provide official guidance for migration, best practices, capabilities, FAQ, and
    support.

  What blocked deeper access:

  - The developer.fedex.com/api/en-us/catalog.html and related portal pages rendered only a shell with “Something went
    wrong” / “Your session has timed out.”

  - The API catalog itself did not expose the underlying API detail pages or downloadable specs through the accessible
    tool.

  - I did not find official OpenAPI/Swagger downloads, PDF specs, or Postman collections through the accessible pages in
    this session.

  Verified official FedEx sources used:

  - Developer Portal Home (https://developer.fedex.com/api/en-us/home.html)
  - API Catalog (https://developer.fedex.com/api/en-us/catalog.html)
  - Guides (https://developer.fedex.com/api/en-us/guides.html)
  - Quotas & rate limits guide (https://developer.fedex.com/api/en-us/guides/ratelimits.html)
  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - FedEx Integration Solutions Home (https://www.fedex.com/en-us/integration.html)
  - Learn about integration (https://www.fedex.com/en-us/integration/learn.html)
  - What is an API (https://www.fedex.com/en-us/integration/api.html)
  - Features and capabilities (https://www.fedex.com/en-us/integration/features.html)
  - Why migrate (https://www.fedex.com/en-us/integration/migrate.html)
  - FAQ (https://www.fedex.com/en-us/integration/faq.html)
  - Support (https://www.fedex.com/en-us/integration/support.html)

  ## Engineering Report

  ### 1. Authentication Architecture

  Verified guidance:

  - FedEx REST APIs use OAuth token-based authentication.
  - FedEx explicitly recommends token caching and says not to make repeated token calls; the token should be reused
    until a 401 is observed, then regenerated.

  - Tokens must remain server-side and never be exposed to the browser.
  - API keys and secrets must be stored securely and not hardcoded.
  - FedEx recommends TLS 1.2+ and HTTPS for all transactions.

  Key official guidance:

  - Use separate sandbox and production base URIs.
  - Sandbox base: https://apis-sandbox.fedex.com/
  - Production base: https://apis.fedex.com/
  - The token is valid for an hour, and FedEx says to use it for the full hour and request a new one only when it
    expires.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - Quotas & rate limits guide (https://developer.fedex.com/api/en-us/guides/ratelimits.html)

  Recommended plugin implication:

  - The plugin should cache access tokens with an expiry timestamp.
  - It should reuse the token until near expiry or until a 401 response occurs.
  - Token rotation should happen server-side only.

  ### 2. Rates API Architecture

  Verified official guidance:

  - FedEx Rate can be used either for a specific serviceType or as a rate shop request when no service type is provided.
  - FedEx recommends using Service Availability to determine which services/package options are valid for a lane before
    sending Rate or Ship requests.

  - FedEx warns not to hardcode business rules like service types, package types, or weight limits because they can
    change.

  - FedEx warns that for weight and currency, only two decimal places should be used; dimensions should not use
    decimals.

  - Required fields should be validated before sending.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)

  Important implications for the plugin:

  - The current rate flow should remain dynamic and should not hardcode a fixed FedEx service set.
  - Validation should happen before API submission.
  - The plugin should be prepared for empty or missing reply sections.

  ### 3. Shipping Architecture

  Verified official guidance:

  - Shipping labels and shipment creation are core FedEx API capabilities.
  - FedEx recommends using Service Availability to determine valid services/package options for a shipment.
  - For special services, the special service type and its details must be included.
  - For FedEx Ground, close procedures should be performed at the end of the shipping day before pickup.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - Features and capabilities (https://www.fedex.com/en-us/integration/features.html)

  Implication:

  - For WooCommerce shipping, rating is only one part of the future integration. Shipment creation should use the same
    service validation principles and keep special service handling separate.

  ### 4. Tracking Architecture

  Verified official guidance:

  - Tracking is a core FedEx capability.
  - FedEx recommends limiting a single track request to 30 tracking numbers.
  - FedEx recommends removing delivered packages from batch tracking.
  - FedEx notes tracking numbers can be reused, so date ranges help avoid duplicate results.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - Features and capabilities (https://www.fedex.com/en-us/integration/features.html)

  Implication:

  - A future tracking implementation should be batch-aware, date-aware, and should not over-query.

  ### 5. Address Validation Architecture

  Verified official guidance:

  - Address Validation is a suggestion, not a final determination.
  - It must not be a hard dependency for order processing.
  - Orders should still be able to proceed when address validation is unavailable.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - FAQ (https://www.fedex.com/en-us/integration/faq.html)

  Implication:

  - Address validation belongs in checkout as an assistive step, not a blocking gate.

  ### 6. Error Handling Strategy

  Verified official status guidance:

  - 200 OK means success.
  - 400 bad request.
  - 401 unauthorized.
  - 403 forbidden.
  - 404 not found.
  - 405 method not allowed.
  - 409 conflict.
  - 415 unsupported media type.
  - 422 unprocessable entity.
  - 429 too many requests.
  - 500 failure.
  - 503 service unavailable.

  FedEx best practices:

  - Log and parse notes and warnings.
  - Do not key logic off message text alone because messages can change.
  - Use error codes and HTTP status.
  - Build logic to avoid the same transaction failing repeatedly.

  Sources:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - Quotas & rate limits guide (https://developer.fedex.com/api/en-us/guides/ratelimits.html)

  ### 7. Production Readiness Assessment

  Verified FedEx guidance:

  - Use test URIs during development, production URIs in production.
  - Do not run performance testing in test or production environments.
  - Use sandbox for testing and production for real transactions.
  - Some APIs may require certification depending on the API.
  - FedEx notes that API documentation, schema details, and sample code are part of the intended integration path.

  Sources:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)
  - FAQ (https://www.fedex.com/en-us/integration/faq.html)

  ### 8. Security Review

  Verified FedEx guidance:

  - Never expose API keys, secrets, or tokens to the browser.
  - Do not hardcode credentials.
  - Store tokens server-side only.
  - Use HTTPS and TLS 1.2+.
  - Cache the token rather than repeatedly requesting it.

  Source:

  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)

  ### 9. Performance Recommendations

  Verified FedEx guidance:

  - Respect the project rate limit of 1,400 transactions per 10 seconds.
  - Quotas are enforced at organization and project level.
  - Thresholds apply at the IP level for auth token generation.
  - Avoid repeated failure loops.
  - Use filtering and sorting where relevant.

  Sources:

  - Quotas & rate limits guide (https://developer.fedex.com/api/en-us/guides/ratelimits.html)
  - Best practices guide (https://developer.fedex.com/api/en-us/guides/best-practices.html)

  ### 10. Comparison with Current Plugin Implementation

  Based on the repository state I already inspected earlier:

  - FedExAuthService currently requests a token but does not implement token expiry tracking or 401-triggered refresh
    logic.

  - FedExApiClient already selects sandbox vs production endpoints correctly.
  - RateRequestBuilder validates core fields and builds a structured request, but its payload should be re-checked
    against the actual FedEx API docs once they are accessible.

  - RateResponseParser is defensive and can handle missing fields, which aligns with FedEx guidance to expect partial
    responses.

  - FedExRatesService already separates orchestration from request building and response parsing, which matches the
    architecture FedEx integrations benefit from.

  - The plugin currently treats rate shopping as the primary flow, which matches the documented “Rate shop” behavior.

  ### 11. Risks Identified

  - Portal documentation access is incomplete in this environment, so some API-specific request/response details remain
    unverified.

  - The current plugin code likely needs token caching and expiry-aware refresh handling to match FedEx best practices.
  - Service availability is not yet integrated, so rating could be less accurate for lanes where a service is
    unavailable.

  - Shipping and tracking are not yet implemented, so the project is still only a partial FedEx integration.
  - Rate limiting and retry behavior should be made explicit to avoid quota waste and repeated failures.

  ### 12. Recommended Implementation Roadmap

  1. Verify the exact Rate API, Ship API, Track API, and Address Validation payload schemas from readable official FedEx
     docs.

  2. Implement token caching with expiry and 401-triggered refresh.
  3. Add service availability support before rate-shopping where appropriate.
  4. Tighten request validation to match FedEx field-level constraints, especially decimals and optional field handling.
  5. Add explicit error classification for 400/401/403/429/500/503.
  6. Expand the plugin later to Ship, Track, and Address Validation with the same separation of responsibilities.

  ## Access Summary

  Successfully accessed:

  - Developer Portal home
  - Guides
  - Quotas & rate limits
  - Best practices
  - FedEx Integration Solutions pages: API overview, learn, features, migrate, FAQ, support

  Not verified due portal access limits:

  - API Catalog details
  - API Recipes
  - Per-API documentation pages for Rates, Ship, Track, Address Validation, Service Availability
  - Any downloadable OpenAPI/Swagger, PDF, or Postman resources exposed behind the portal

  If you want, I can next do one of two things:

  1. Turn this into a FedEx API implementation checklist mapped to the current plugin classes.
  2. Resume portal exploration specifically for the rate/shipping/tracking/address-validation pages if you can provide a
     logged-in session path or exported docs.

─ Worked for 1m 13s ────────────────────────────────────────────────────────────────────────────────────────────────────

› Explain this codebase
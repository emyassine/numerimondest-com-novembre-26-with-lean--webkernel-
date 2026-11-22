@props([
    'studio_config' => [],
    'site_prefix' => '/',
])

<footer class="nm-footer" id="contact" style="margin-top:5rem;">
    <span class="nm-footer-copy"  style="margin-top:3rem;margin-bottom:2rem;">
    © {{ $studio_config['company']['name'] }} · {{ $studio_config['company']['location'] }} — Page Load took {{ number_format(request_time(), 2, ',', ' ') . ' ms' }}</span>
    <div class="nm-footer-links">
        @foreach (($studio_config['social'] ?? []) as $network => $url)
            <a href="{{ $url }}" target="_blank" rel="noreferrer">{{ $network }}</a>
        @endforeach
        <a href="{{ $studio_config['crm']['client_space'] }}" target="_blank" rel="noreferrer">Client space</a>
        <a href="{{ $studio_config['crm']['client_registration'] }}" target="_blank" rel="noreferrer">Create account</a>
        <a href="{{ $studio_config['crm']['staff_login'] }}" target="_blank" rel="noreferrer">Staff login</a>
    </div>
    <a class="nm-footer-email" href="{{ $studio_config['contact']['phone']['href'] }}">
        {{ $studio_config['contact']['phone']['display'] }}
    </a>
    <a class="nm-footer-privacy" href="{{ $site_prefix }}/privacy">Privacy policy</a>
    <a class="nm-footer-privacy" href="{{ $site_prefix }}/pricing-policy">Pricing &amp; engagement</a>
</footer>

<style nonce="{{ $csp_nonce ?? '' }}">
.nm-footer {
  padding: 25px 50px;
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  gap: 30px;
}

.nm-footer-links { display: flex; gap: 25px; align-items: center; }

.nm-footer-links a,
.nm-footer-email,
.nm-footer-privacy,
.nm-footer-copy {
  color: var(--nm-ink);
  font: 400 10px/1.2 var(--nm-title);
  letter-spacing: .12em;
  text-decoration: none;
  text-transform: uppercase;
}

.nm-footer-links a:hover,
.nm-footer-email:hover,
.nm-footer-privacy:hover { opacity: .56; }

.nm-footer-copy { grid-column: 1 / -1; text-align: center; }

@media (max-width: 700px) {
  .nm-footer { padding: 25px 20px; grid-template-columns: 1fr; justify-items: center; gap: 20px; }
  .nm-footer-links { flex-wrap: wrap; justify-content: center; gap: 15px; }
  .nm-footer-email, .nm-footer-privacy { justify-self: center; }
  .nm-footer-copy { grid-column: 1; }
}
</style>

@props([
    'effect' => ['silk', ['inject' => []]],
    'heroStyle' => [],
    'eyebrow' => [],
    'title' => [],
    'description' => [],
    'buttons' => [],
    'isDark' => false,
])

@php
    $effectName = is_array($effect) && is_string($effect[0] ?? null) ? $effect[0] : 'silk';
    $effectInject = is_array($effect[1]['inject'] ?? null) ? $effect[1]['inject'] : [];
    $lightColor = $effectInject['lightColor'] ?? '#0043e0';
    $darkColor = $effectInject['darkColor'] ?? '#141D2A';
    $noiseIntensity = (float) ($effectInject['noiseIntensity'] ?? 0.3);
    $lightNoise = (float) ($effectInject['lightNoise'] ?? $noiseIntensity);
    $speed = (float) ($effectInject['speed'] ?? 12);
    $scale = (float) ($effectInject['scale'] ?? 1);
    $rotation = (float) ($effectInject['rotation'] ?? 0);
    $textColor = '#e2e8f0';
    $heroStyleString = '';
    foreach (is_array($heroStyle) ? $heroStyle : [] as $property => $value) {
        if (! is_string($property) || (! is_string($value) && ! is_numeric($value))) {
            continue;
        }
        $cssProperty = strtolower((string) preg_replace('/([A-Z])/', '-$1', $property));
        $heroStyleString .= $cssProperty.':'.(string) $value.';';
    }
    $eyebrowText = is_array($eyebrow) ? (string) ($eyebrow['text'] ?? '') : (string) $eyebrow;
    $titleText = is_array($title) ? (string) ($title['text'] ?? '') : (string) $title;
    $descriptionText = is_array($description) ? (string) ($description['text'] ?? '') : (string) $description;
    $resolveTextColor = static function (mixed $color) use ($isDark, $textColor): string {
        if (is_string($color)) {
            return $color;
        }
        if (is_array($color)) {
            $resolved = $color[$isDark ? 'dark' : 'light'] ?? null;
            return is_string($resolved) ? $resolved : $textColor;
        }
        return $textColor;
    };
    $eyebrowColor = $resolveTextColor(is_array($eyebrow) ? ($eyebrow['color'] ?? null) : null);
    $titleColor = $resolveTextColor(is_array($title) ? ($title['color'] ?? null) : null);
    $descriptionColor = $resolveTextColor(is_array($description) ? ($description['color'] ?? null) : null);
@endphp

<section
    {{ $attributes->class(['nm-hero-effect', 'nm-hero-effect--dark' => $isDark, 'nm-hero-effect--light' => ! $isDark]) }}
    data-nm-hero-effect="{{ $effectName }}"
    data-light-color="{{ $lightColor }}"
    data-dark-color="{{ $darkColor }}"
    data-noise-intensity="{{ $noiseIntensity }}"
    data-light-noise="{{ $lightNoise }}"
    data-speed="{{ $speed }}"
    data-scale="{{ $scale }}"
    data-rotation="{{ $rotation }}"
    data-force-light="{{ config('dependable-ui.force_light_mode', false) ? 'true' : 'false' }}"
    style="{{ $heroStyleString }}"
>
    <div class="nm-hero-effect__background" aria-hidden="true"><canvas></canvas></div>
    <div class="nm-hero-effect__content">
        @if ($eyebrowText !== '')
            <p class="nm-hero-effect__eyebrow" style="color:{{ $eyebrowColor }}">{!! $eyebrowText !!}</p>
        @endif
        <h1 style="color:{{ $titleColor }}">{!! $titleText !!}</h1>
        <p class="nm-hero-effect__description" style="color:{{ $descriptionColor }}">{!! $descriptionText !!}</p>
        @if (is_array($buttons) && $buttons !== [])
            <div class="nm-hero-effect__actions">
                @foreach ($buttons as $button)
                    @if (is_array($button) && is_string($button['label'] ?? null) && is_string($button['href'] ?? null))
                        <x-webkernel-platform::button
                            tag="a"
                            :href="$button['href']"
                            :target="! empty($button['external']) ? '_blank' : null"
                            :rel="! empty($button['external']) ? 'noopener noreferrer' : null"
                            :color="$button['color'] ?? ($loop->first ? 'primary' : 'gray')"
                            size="md"
                            class="nm-hero-effect__button"
                        >
                            @if (($button['icon'] ?? null) === 'whatsapp')
                                <x-webkernel::icon icon="whatsapp" set="simple-icons" width="16" height="16" aria-hidden="true" />
                            @endif
                            {{ $button['label'] }}
                        </x-webkernel-platform::button>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>

<style>
    .nm-hero-effect {
        position: relative;
        z-index: 0;
        display: grid;
        width: 100vw;
        min-height: clamp(34rem, 70svh, 46rem);
        place-items: center;
        margin: 0 calc(50% - 50vw) 3rem;
        overflow: hidden;
        isolation: isolate;
        background: #141d2a;
    }
    .nm-hero-effect__background,
    .nm-hero-effect__background canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }
    .nm-hero-effect__background canvas { display: block; }
    .nm-hero-effect__content {
        position: relative;
        z-index: 1;
        width: min(100% - 2rem, 820px);
        padding: 3.5rem 0;
        text-align: center;
        pointer-events: none;
    }
    .nm-hero-effect__content h1 {
        max-width: 760px;
        margin: 0 auto;
        font: 500 clamp(2rem, 3.6vw, 3.25rem)/1.08 var(--nm-title, BrownLLMain, Arial, sans-serif);
        letter-spacing: -.04em;
    }
    .nm-hero-effect__eyebrow {
        margin: 0 auto 1rem;
        font: 600 .75rem/1.5 var(--nm-title, BrownLLMain, Arial, sans-serif);
        letter-spacing: .16em;
        text-transform: uppercase;
    }
    .nm-hero-effect__description {
        max-width: 590px;
        margin: 1rem auto 0;
        font: 400 1rem/1.6 var(--nm-body, BrownLLMain, Arial, sans-serif);
    }
    .nm-hero-effect__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .625rem;
        margin-top: 1.5rem;
        pointer-events: auto;
    }
    .nm-hero-effect__actions :is(a,button) { pointer-events: auto; }
    .nm-hero-effect__button { min-height: 2.25rem; border-radius: var(--radius-md); font-size: .8125rem; }
    .nm-hero-effect__button svg { width: 1rem; height: 1rem; flex: none; }
    @media (max-width: 640px) {
        .nm-hero-effect { min-height: 34rem; margin-top: -20px; }
        .nm-hero-effect__content { padding: 2.5rem 0; }
        .nm-hero-effect__content h1 { font-size: clamp(2rem, 8vw, 2.75rem); }
        .nm-hero-effect__actions { gap: .5rem; margin-top: 1.5rem; }
    }
</style>

<script>
(() => {
    const root = document.currentScript.previousElementSibling?.previousElementSibling;
    const canvas = root?.querySelector('canvas');
    if (!root || !canvas || root.dataset.nmHeroEffect !== 'silk') return;
    const gl = canvas.getContext('webgl', { alpha: false, antialias: true, powerPreference: 'low-power' });
    if (!gl) return;

    const vertexSource = `attribute vec2 position; varying vec2 vUv; void main(){ vUv=position*.5+.5; gl_Position=vec4(position,0.,1.); }`;
    const fragmentSource = `
        precision highp float;
        varying vec2 vUv;
        uniform float uTime;
        uniform float uSpeed;
        uniform float uScale;
        uniform float uRotation;
        uniform float uNoiseIntensity;
        uniform vec3 uColor;
        const float e=2.71828182845904523536;
        float noise(vec2 p){ float G=e; vec2 r=G*sin(G*p); return fract(r.x*r.y*(1.+p.x)); }
        void main(){
            float c=cos(uRotation), s=sin(uRotation);
            vec2 tex=mat2(c,-s,s,c)*(vUv*uScale);
            tex*=uScale;
            float tOffset=uSpeed*uTime;
            tex.y+=.03*sin(8.*tex.x-tOffset);
            float pattern=.6+.4*sin(5.*(tex.x+tex.y+cos(3.*tex.x+5.*tex.y)+.02*tOffset)+sin(20.*(tex.x+tex.y-.1*tOffset)));
            vec3 color=uColor*pattern-noise(gl_FragCoord.xy)/15.*uNoiseIntensity;
            gl_FragColor=vec4(color,1.);
        }
    `;
    const makeShader = (type, source) => {
        const shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        return gl.getShaderParameter(shader, gl.COMPILE_STATUS) ? shader : null;
    };
    const vertexShader = makeShader(gl.VERTEX_SHADER, vertexSource);
    const fragmentShader = makeShader(gl.FRAGMENT_SHADER, fragmentSource);
    if (!vertexShader || !fragmentShader) return;
    const program = gl.createProgram();
    gl.attachShader(program, vertexShader);
    gl.attachShader(program, fragmentShader);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return;
    gl.useProgram(program);

    const vertices = new Float32Array([-1,-1, 1,-1, -1,1, -1,1, 1,-1, 1,1]);
    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, vertices, gl.STATIC_DRAW);
    const position = gl.getAttribLocation(program, 'position');
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
    const timeUniform = gl.getUniformLocation(program, 'uTime');
    const speedUniform = gl.getUniformLocation(program, 'uSpeed');
    const scaleUniform = gl.getUniformLocation(program, 'uScale');
    const rotationUniform = gl.getUniformLocation(program, 'uRotation');
    const noiseUniform = gl.getUniformLocation(program, 'uNoiseIntensity');
    const colorUniform = gl.getUniformLocation(program, 'uColor');
    gl.uniform1f(speedUniform, Number(root.dataset.speed) || 12);
    gl.uniform1f(scaleUniform, Number(root.dataset.scale) || 1);
    gl.uniform1f(rotationUniform, Number(root.dataset.rotation) || 0);

    const colorToRgb = (value) => {
        const hex = /^#[\da-f]{6}$/i.test(value) ? value.slice(1) : '0043e0';
        return [0, 2, 4].map(index => parseInt(hex.slice(index, index + 2), 16) / 255);
    };
    const forceLight = root.dataset.forceLight === 'true';
    const darkMedia = window.matchMedia('(prefers-color-scheme: dark)');
    const updateColor = () => {
        const dark = !forceLight && (document.documentElement.classList.contains('dark') || darkMedia.matches);
        gl.uniform3fv(colorUniform, colorToRgb(dark ? root.dataset.darkColor : root.dataset.lightColor));
        gl.uniform1f(noiseUniform, dark ? Number(root.dataset.noiseIntensity) : Number(root.dataset.lightNoise));
        root.classList.toggle('nm-hero-effect--dark', dark);
        root.classList.toggle('nm-hero-effect--light', !dark);
    };
    updateColor();
    darkMedia.addEventListener?.('change', updateColor);

    const resize = () => {
        const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
        const width = Math.max(1, Math.floor(root.clientWidth * ratio));
        const height = Math.max(1, Math.floor(root.clientHeight * ratio));
        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
            gl.viewport(0, 0, width, height);
        }
    };
    const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(resize) : null;
    if (resizeObserver) resizeObserver.observe(root);
    else window.addEventListener('resize', resize);
    resize();

    let frame = 0;
    let startedAt = performance.now();
    let visible = true;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const render = now => {
        frame = 0;
        gl.uniform1f(timeUniform, reduceMotion ? 0 : (now - startedAt) / 10000);
        gl.drawArrays(gl.TRIANGLES, 0, 6);
        if (!reduceMotion && visible) frame = requestAnimationFrame(render);
    };
    const visibilityObserver = 'IntersectionObserver' in window
        ? new IntersectionObserver(([entry]) => {
            visible = entry.isIntersecting;
            if (visible && !frame && !reduceMotion) frame = requestAnimationFrame(render);
            if (!visible && frame) { cancelAnimationFrame(frame); frame = 0; }
        })
        : null;
    visibilityObserver?.observe(root);
    render(performance.now());
    window.addEventListener('pagehide', () => {
        if (frame) cancelAnimationFrame(frame);
        resizeObserver?.disconnect();
        visibilityObserver?.disconnect();
        window.removeEventListener('resize', resize);
        darkMedia.removeEventListener?.('change', updateColor);
        gl.getExtension('WEBGL_lose_context')?.loseContext();
    }, { once: true });
})();
</script>

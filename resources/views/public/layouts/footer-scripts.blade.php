@if(!Helper::isLighthouse())
    <!-- Load Custom CSS Start -->
    <script>loadCSS( "{{ mix("css/invisible.css") }}", false, "all" );</script>
    <!-- Load Custom CSS End -->

    <!-- Optimized loading JS Start -->
    <script>var scr = {"scripts":[
                {"src" : "{{ mix("js/app.js") }}", "async" : false}
            ]};!function(t,n,r){"use strict";var c=function(t){if("[object Array]"!==Object.prototype.toString.call(t))return!1;for(var r=0;r<t.length;r++){var c=n.createElement("script"),e=t[r];c.src=e.src,c.async=e.async,n.body.appendChild(c)}return!0};t.addEventListener?t.addEventListener("load",function(){c(r.scripts);},!1):t.attachEvent?t.attachEvent("onload",function(){c(r.scripts)}):t.onload=function(){c(r.scripts)}}(window,document,scr);
    </script>
    <!-- Optimized loading JS End -->
    <script type="module">
        import {spline} from "https://cdn.skypack.dev/@georgedoescode/spline@1.0.1";
        import SimplexNoise from "https://cdn.skypack.dev/simplex-noise@2.4.0";

        const blobElements = document.querySelectorAll(".bg-blob path");
        const simplex = new SimplexNoise();
        const noiseStep = 0.005;

        blobElements.forEach((path) => {
            const points = createPoints();
            let localNoiseStep = noiseStep;

            function animate() {
                path.setAttribute("d", spline(points, 1, true));

                for (let i = 0; i < points.length; i++) {
                    const point = points[i];
                    const nX = noise(point.noiseOffsetX, point.noiseOffsetX);
                    const nY = noise(point.noiseOffsetY, point.noiseOffsetY);
                    const x = map(nX, -1, 1, point.originX - 20, point.originX + 20);
                    const y = map(nY, -1, 1, point.originY - 20, point.originY + 20);

                    point.x = x;
                    point.y = y;

                    point.noiseOffsetX += localNoiseStep;
                    point.noiseOffsetY += localNoiseStep;
                }

                requestAnimationFrame(animate);
            }

            path.addEventListener("mouseover", () => {
                localNoiseStep = 0.01;
            });

            path.addEventListener("mouseleave", () => {
                localNoiseStep = noiseStep;
            });

            animate();
        });

        function map(n, start1, end1, start2, end2) {
            return ((n - start1) / (end1 - start1)) * (end2 - start2) + start2;
        }

        function noise(x, y) {
            return simplex.noise2D(x, y);
        }

        function createPoints() {
            const points = [];
            const numPoints = 6;
            const angleStep = (Math.PI * 2) / numPoints;
            const rad = 75;

            for (let i = 1; i <= numPoints; i++) {
                const theta = i * angleStep;
                const x = 100 + Math.cos(theta) * rad;
                const y = 100 + Math.sin(theta) * rad;

                points.push({
                    x: x,
                    y: y,
                    originX: x,
                    originY: y,
                    noiseOffsetX: Math.random() * 1000,
                    noiseOffsetY: Math.random() * 1000
                });
            }
            return points;
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r121/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
    <script>
        if(document.getElementsByClassName('waves-bg').length){
            document.addEventListener('DOMContentLoaded', function () {
                VANTA.WAVES({
                    el: ".waves-bg",
                    mouseControls: true,
                    touchControls: true,
                    gyroControls: false,
                    minHeight: 200.00,
                    minWidth: 200.00,
                    scale: 1.00,
                    scaleMobile: 1.00,
                    color: 0x141414,
                    shininess: 32.00,
                    waveHeight: 31.50,
                    zoom: 1.50
                })
            });
        }
    </script>
@endif

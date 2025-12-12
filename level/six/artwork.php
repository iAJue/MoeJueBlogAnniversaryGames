<?php
setcookie('url', 'last/safe.html', time() + 86400, '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>画廊 - 阿珏酱Blog十周年庆解密游戏</title>
    <meta name="description" content="阿珏酱Blog十周年庆解密游戏，参与解密游戏，赢取神秘大奖！">
    <meta name="keywords" content="阿珏酱Blog十周年庆解密游戏, 解密游戏, 神秘大奖">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --edge-thickness-px: 350;
            --edge-gap-px: 10;
            --edge-item-size-px: 300;
            --edge-speed-px-per-s: 32;
            --edge-thickness: calc(var(--edge-thickness-px) * 1px);
            --edge-gap: calc(var(--edge-gap-px) * 1px);
            --edge-item-size: calc(var(--edge-item-size-px) * 1px);
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #ffffff;
            color: #111111;
            pointer-events: none;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .art-container {
            text-align: center;
            max-width: 450px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .art-container img {
            width: 100%;
            height: auto;
            box-shadow: none;
        }

        .caption {
            order: -1;
            font-size: 1.1rem;
            letter-spacing: 0.05em;
        }

        .edge {
            position: fixed;
            overflow: hidden;
            pointer-events: none;
            z-index: 5;
        }

        .edge-track {
            display: flex;
            gap: var(--edge-gap);
            will-change: transform;
        }

        .edge-track img {
            width: var(--edge-item-size);
            height: var(--edge-item-size);
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .edge-vertical {
            top: 0;
            bottom: 0;
        }

        .edge-left {
            left: 0;
        }

        .edge-right {
            right: 0;
        }

        .edge-vertical .edge-track {
            flex-direction: column;
            padding: 16px 10px;
        }

        .edge-horizontal {
            left: var(--edge-thickness);
            right: var(--edge-thickness);
            height: var(--edge-thickness);
        }

        .edge-top {
            top: 0;
        }

        .edge-horizontal .edge-track {
            align-items: center;
            padding: 10px 16px;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #000000;
                color: #f3f3f3;
            }

            .edge {
                background-color: rgba(255, 255, 255, 0.04);
            }

            .edge-track img {
                box-shadow: 0 0 12px rgba(0, 0, 0, 0.5);
            }
        }
    </style>
</head>
<body>
    <div class="edge edge-left edge-vertical">
        <div class="edge-track" data-track="left" data-axis="y" data-direction="1"></div>
    </div>

    <div class="edge edge-top edge-horizontal">
        <div class="edge-track" data-track="top" data-axis="x" data-direction="-1"></div>
    </div>

    <div class="edge edge-right edge-vertical">
        <div class="edge-track" data-track="right" data-axis="y" data-direction="-1"></div>
    </div>

    <div class="art-container">
        <div class="caption">看累了吗,神奇的小饼干,你要来一点吗?</div>
        <img src="../../images/image.png" alt="Artwork">
    </div>
    <script>
        (function () {
            const totalImages = 10;
            const maxChapterNumber = 50;
            const maxPageNumber = 10;
            const folderPrefix = "别当欧尼酱了！";
            const remotePrefix = "https://gh-proxy.com/github.com/RShock/zhenxun_plugin_onimai/blob/main/src/comic/";

            const padTwo = function (value) {
                return String(value).padStart(2, "0");
            };

            const buildImageRecord = function (chapterNumber, pageNumber) {
                const chapterLabel = folderPrefix + padTwo(chapterNumber);
                const folderSegment = encodeURIComponent(chapterLabel);
                const pageLabel = padTwo(pageNumber);
                return {
                    src: remotePrefix + folderSegment + "/" + pageLabel + ".jpg",
                    chapterLabel: chapterLabel,
                    pageLabel: pageLabel
                };
            };

            const randomRecord = function () {
                const chapterNumber = Math.floor(Math.random() * maxChapterNumber) + 1;
                const pageNumber = Math.floor(Math.random() * maxPageNumber) + 1;
                return buildImageRecord(chapterNumber, pageNumber);
            };

            const imageSources = Array.from({ length: totalImages }, randomRecord);
            const loopSources = imageSources.concat(imageSources, imageSources);
            const trackSequence = ["top", "left", "right"];
            const rootStyles = getComputedStyle(document.documentElement);
            const itemSize = parseFloat(rootStyles.getPropertyValue("--edge-item-size-px")) || 96;
            const gapSize = parseFloat(rootStyles.getPropertyValue("--edge-gap-px")) || 12;
            const edgeSpeed = parseFloat(rootStyles.getPropertyValue("--edge-speed-px-per-s")) || 32;
            const cycleLength = (itemSize + gapSize) * imageSources.length;
            const trackMeta = new Map();

            document.querySelectorAll("[data-track]").forEach(function (track) {
                const trackName = track.getAttribute("data-track");
                loopSources.forEach(function (imageMeta) {
                    const img = document.createElement("img");
                    img.src = imageMeta.src;
                    img.alt = imageMeta.chapterLabel + " - 第" + imageMeta.pageLabel + "页";
                    img.draggable = false;
                    track.appendChild(img);
                });
                trackMeta.set(trackName, {
                    name: trackName,
                    el: track,
                    axis: track.getAttribute("data-axis"),
                    direction: Number(track.getAttribute("data-direction") || 1),
                    segmentLength: 0,
                    startOffset: 0
                });
            });

            let totalPathLength = 0;

            function updateSegments() {
                let cumulative = 0;
                trackSequence.forEach(function (name) {
                    const meta = trackMeta.get(name);
                    if (!meta) {
                        return;
                    }
                    const wrapperRect = meta.el.parentElement.getBoundingClientRect();
                    meta.segmentLength = meta.axis === "x" ? wrapperRect.width : wrapperRect.height;
                    meta.startOffset = cumulative;
                    cumulative += meta.segmentLength;
                });
                totalPathLength = cumulative;
            }

            updateSegments();
            window.addEventListener("resize", updateSegments);

            let offset = 0;
            let lastTime = performance.now();

            function animate(now) {
                const deltaSeconds = Math.min((now - lastTime) / 1000, 0.05);
                lastTime = now;
                if (totalPathLength === 0) {
                    requestAnimationFrame(animate);
                    return;
                }

                offset = (offset + edgeSpeed * deltaSeconds) % totalPathLength;

                trackSequence.forEach(function (name) {
                    const meta = trackMeta.get(name);
                    if (!meta || meta.segmentLength === 0) {
                        return;
                    }
                    const relative = (offset - meta.startOffset + totalPathLength) % meta.segmentLength;
                    const offsetInCycle = relative % cycleLength;
                    const translation = meta.direction === 1
                        ? -cycleLength + offsetInCycle
                        : -offsetInCycle;
                    const transformValue = meta.axis === "x"
                        ? "translateX(" + translation + "px)"
                        : "translateY(" + translation + "px)";
                    meta.el.style.transform = transformValue;
                });

                requestAnimationFrame(animate);
            }

            requestAnimationFrame(animate);

            const blockMouseEvent = function (event) {
                event.preventDefault();
                event.stopPropagation();
            };
            ['click', 'mousedown', 'mouseup', 'contextmenu'].forEach(function (eventName) {
                document.addEventListener(eventName, blockMouseEvent, true);
            });
        })();
    </script>
</body>
</html>

<?php
setcookie('url', 'puzzles', time() + 86400, '/');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>漫画 - 阿珏酱Blog十周年解密游戏</title>
    <meta name="description" content="中间画册可手动翻页，左右下角图片随深浅色模式切换显示。">
    <meta name="keywords" content="阿珏酱Blog十周年解密游戏, 画册翻页, 深浅色模式">
    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --book-width: clamp(360px, 44vw, 680px);
            --book-height: clamp(520px, 84vh, 980px);
            --corner-image-width: clamp(150px, 19vw, 320px);
            --cover-1: #6b4a33;
            --cover-2: #3f2b1e;
            --paper: #efe2bf;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #e7dfd1;
            background-color: #000000;
            user-select: none;
            -webkit-user-select: none;
        }

        .corner-art {
            position: fixed;
            bottom: 14px;
            width: var(--corner-image-width);
            height: auto;
            pointer-events: none;
            z-index: 1;
            opacity: 0.96;
        }

        .corner-left {
            left: 14px;
            display: block;
        }

        .corner-right {
            right: 14px;
            display: none;
        }

        .flip-book {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .book-title {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #f6eddc;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.5);
        }

        .book-view {
            width: var(--book-width);
            height: var(--book-height);
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(145deg, var(--cover-1), var(--cover-2));
            border: 1px solid rgba(82, 58, 39, 0.8);
            box-shadow:
                0 28px 56px rgba(0, 0, 0, 0.58),
                inset 0 0 0 1px rgba(255, 250, 238, 0.16);
            cursor: grab;
            outline: none;
            perspective: 1800px;
            opacity: 0;
            transition: opacity 0.2s ease-out;
        }

        .book-view.is-ready {
            opacity: 1;
        }

        .book-view:active {
            cursor: grabbing;
        }

        .book-view:focus-visible {
            box-shadow:
                0 0 0 3px rgba(255, 248, 225, 0.95),
                0 28px 56px rgba(0, 0, 0, 0.58),
                inset 0 0 0 1px rgba(255, 250, 238, 0.16);
        }

        .book-view::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 18px;
            background: linear-gradient(to right, rgba(28, 15, 9, 0.86), rgba(119, 80, 56, 0.58) 40%, rgba(255, 246, 219, 0.22));
            z-index: 5;
            pointer-events: none;
        }

        .book-view::after {
            content: "";
            position: absolute;
            top: 10px;
            bottom: 10px;
            right: 9px;
            width: 10px;
            border-radius: 3px;
            background: repeating-linear-gradient(
                to bottom,
                rgba(255, 241, 208, 0.94),
                rgba(255, 241, 208, 0.94) 2px,
                rgba(199, 163, 118, 0.76) 2px,
                rgba(199, 163, 118, 0.76) 4px
            );
            z-index: 5;
            pointer-events: none;
        }

        .book-page {
            position: absolute;
            top: 12px;
            bottom: 12px;
            left: 20px;
            right: 14px;
            border-radius: 8px;
            background-color: var(--paper);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.24);
        }

        .page-under {
            z-index: 1;
            filter: none;
        }

        .page-current {
            z-index: 4;
            transition: opacity 0.15s linear;
        }

        .page-flip {
            z-index: 3;
            transform-origin: left center;
            backface-visibility: hidden;
            pointer-events: none;
            will-change: transform;
            transform: translateZ(0);
        }

        .book-shadow {
            position: absolute;
            top: 12px;
            bottom: 12px;
            left: 20px;
            right: 14px;
            border-radius: 8px;
            background: linear-gradient(to left, rgba(20, 12, 8, 0.56), rgba(20, 12, 8, 0));
            opacity: 0;
            z-index: 4;
            pointer-events: none;
        }

        .book-status {
            font-size: 12px;
            letter-spacing: 0.06em;
            color: rgba(243, 233, 217, 0.9);
        }

        @media (prefers-color-scheme: light) {
            body {
                color: #1d1a15;
                background-color: #f5f5f5;
            }

            .corner-left {
                display: none;
            }

            .corner-right {
                display: block;
            }

            .book-title {
                color: #2e2015;
                text-shadow: none;
            }

            .book-status {
                color: #3a2819;
            }
        }

        @media (max-width: 900px) {
            :root {
                --book-width: clamp(300px, 60vw, 560px);
                --book-height: clamp(460px, 78vh, 800px);
                --corner-image-width: clamp(120px, 18vw, 220px);
            }
        }

        @media (max-width: 640px) {
            :root {
                --book-width: clamp(240px, 64vw, 360px);
                --book-height: clamp(390px, 72vh, 640px);
                --corner-image-width: clamp(96px, 24vw, 160px);
            }

            .corner-art {
                bottom: 8px;
            }

            .corner-left {
                left: 8px;
            }

            .corner-right {
                right: 8px;
            }
        }
    </style>
</head>
<body>
    <img class="corner-art corner-left" src="../../images/image.png">

    <section class="flip-book" data-book="main">
        <div class="book-title">别当欧尼酱了！</div>
        <div class="book-view" tabindex="0" aria-label="中间画册">
            <div class="book-page page-under"></div>
            <div class="book-page page-current"></div>
            <div class="book-page page-flip"></div>
            <div class="book-shadow"></div>
        </div>
        <div class="book-status"></div>
    </section>

    <img class="corner-art corner-right" src="../../images/image.png">

    <script>
        (function () {
            const totalPages = 16;
            const maxChapterNumber = 50;
            const maxPageNumber = 10;
            const folderPrefix = decodeURIComponent("%E5%88%AB%E5%BD%93%E6%AC%A7%E5%B0%BC%E9%85%B1%E4%BA%86%EF%BC%81");
            const remotePrefix = "https://gh-proxy.com/github.com/RShock/zhenxun_plugin_onimai/blob/main/src/comic/";
            function padTwo(value) {
                return String(value).padStart(2, "0");
            }
            function buildImageRecord(chapterNumber, pageNumber) {
                const chapterLabel = folderPrefix + padTwo(chapterNumber);
                const folderSegment = encodeURIComponent(chapterLabel);
                const pageLabel = padTwo(pageNumber);
                return {
                    src: remotePrefix + folderSegment + "/" + pageLabel + ".jpg",
                    chapterLabel: chapterLabel,
                    pageLabel: pageLabel
                };
            }
            function buildSequentialRecords(total) {
                const records = [];
                let chapter = 1;
                let page = 1;
                for (let i = 0; i < total; i += 1) {
                    records.push(buildImageRecord(chapter, page));
                    page += 1;
                    if (page > maxPageNumber) {
                        page = 1;
                        chapter += 1;
                        if (chapter > maxChapterNumber) {
                            chapter = 1;
                        }
                    }
                }
                return records;
            }
            function applyPage(layer, record) {
                if (!layer || !record) {
                    return;
                }
                layer.style.backgroundImage =
                    "linear-gradient(130deg, rgba(255,255,255,0.16), rgba(0,0,0,0.16)), url('" + record.src + "')";
                layer.setAttribute("aria-label", record.chapterLabel + " page " + record.pageLabel);
            }
            const root = document.querySelector('[data-book="main"]');
            if (!root) {
                return;
            }
            const view = root.querySelector(".book-view");
            const under = root.querySelector(".page-under");
            const current = root.querySelector(".page-current");
            const flip = root.querySelector(".page-flip");
            const shadow = root.querySelector(".book-shadow");
            const status = root.querySelector(".book-status");
            const pages = buildSequentialRecords(totalPages);
            const imageReadyMap = new Map();
            let pageIndex = 0;
            let busy = false;
            let ready = false;
            let pointerDownX = null;
            function preloadImage(src) {
                if (!src) {
                    return Promise.resolve(false);
                }
                if (imageReadyMap.has(src)) {
                    return imageReadyMap.get(src);
                }
                const task = new Promise(function (resolve) {
                    const image = new Image();
                    let settled = false;
                    function finish(ok) {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        resolve(ok);
                    }
                    image.onload = function () {
                        finish(true);
                    };
                    image.onerror = function () {
                        finish(false);
                    };
                    image.src = src;
                    if (image.complete) {
                        finish(true);
                    }
                });
                imageReadyMap.set(src, task);
                return task;
            }
            function preloadIndex(index) {
                if (index < 0 || index >= pages.length) {
                    return Promise.resolve(false);
                }
                return preloadImage(pages[index].src);
            }
            function preloadNearby(index) {
                preloadIndex(index - 1);
                preloadIndex(index + 1);
                preloadIndex(index + 2);
            }
            function getUnderRecord(index) {
                const underIndex = index + 1 < pages.length ? index + 1 : index;
                return pages[underIndex];
            }
            function refreshUnder(index) {
                applyPage(under, getUnderRecord(index));
            }
            function updateStatus() {
                status.textContent = "Page " + (pageIndex + 1) + " / " + pages.length;
            }
            function setReady() {
                if (ready) {
                    return;
                }
                ready = true;
                view.classList.add("is-ready");
            }
            function finishTurn(nextIndex) {
                pageIndex = nextIndex;
                applyPage(current, pages[pageIndex]);
                refreshUnder(pageIndex);
                flip.style.backgroundImage = "none";
                flip.style.transform = "rotateY(0deg)";
                shadow.style.opacity = "0";
                busy = false;
                updateStatus();
                preloadNearby(pageIndex);
            }
            function runFlipAnimation(fromDeg, toDeg, duration, easing, nextIndex) {
                if (typeof flip.animate !== "function") {
                    finishTurn(nextIndex);
                    return;
                }
                const animation = flip.animate(
                    [
                        { transform: "rotateY(" + fromDeg + "deg)" },
                        { transform: "rotateY(" + toDeg + "deg)" }
                    ],
                    { duration: duration, easing: easing, fill: "forwards" }
                );
                let done = false;
                function complete() {
                    if (done) {
                        return;
                    }
                    done = true;
                    finishTurn(nextIndex);
                }
                animation.onfinish = complete;
                animation.oncancel = complete;
            }
            function turn(step) {
                if (!ready || busy || step === 0) {
                    return;
                }
                const direction = step > 0 ? 1 : -1;
                const nextIndex = pageIndex + direction;
                if (nextIndex < 0 || nextIndex >= pages.length) {
                    return;
                }
                busy = true;
                preloadIndex(nextIndex).finally(function () {
                    const nowPage = pages[pageIndex];
                    const targetPage = pages[nextIndex];
                    const duration = 560;
                    const easing = "cubic-bezier(0.28, 0.78, 0.2, 1)";
                    applyPage(current, targetPage);
                    refreshUnder(nextIndex);
                    shadow.style.opacity = "0";
                    if (direction > 0) {
                        applyPage(flip, nowPage);
                        flip.style.transform = "rotateY(0deg)";
                        runFlipAnimation(0, -170, duration, easing, nextIndex);
                    } else {
                        applyPage(flip, targetPage);
                        flip.style.transform = "rotateY(-170deg)";
                        runFlipAnimation(-170, 0, duration, easing, nextIndex);
                    }
                });
            }
            function turnByPointer(event, deltaX) {
                if (Math.abs(deltaX) > 34) {
                    turn(deltaX < 0 ? 1 : -1);
                    return;
                }
                const rect = view.getBoundingClientRect();
                const localX = event.clientX - rect.left;
                const goNext = localX > rect.width / 2;
                turn(goNext ? 1 : -1);
            }
            applyPage(current, pages[pageIndex]);
            refreshUnder(pageIndex);
            updateStatus();
            preloadIndex(pageIndex).finally(function () {
                setReady();
                preloadNearby(pageIndex);
            });
            view.addEventListener("pointerdown", function (event) {
                if (event.button !== 0) {
                    return;
                }
                pointerDownX = event.clientX;
                view.setPointerCapture(event.pointerId);
            });
            view.addEventListener("pointerup", function (event) {
                if (pointerDownX === null) {
                    return;
                }
                const deltaX = event.clientX - pointerDownX;
                pointerDownX = null;
                turnByPointer(event, deltaX);
            });
            view.addEventListener("pointercancel", function () {
                pointerDownX = null;
            });
            view.addEventListener("wheel", function (event) {
                event.preventDefault();
                if (Math.abs(event.deltaY) < 1.5) {
                    return;
                }
                turn(event.deltaY > 0 ? 1 : -1);
            }, { passive: false });
            view.addEventListener("keydown", function (event) {
                if (event.key === "ArrowRight") {
                    event.preventDefault();
                    turn(1);
                } else if (event.key === "ArrowLeft") {
                    event.preventDefault();
                    turn(-1);
                }
            });
            view.addEventListener("contextmenu", function (event) {
                event.preventDefault();
            });
        })();
    </script>
</body>
</html>

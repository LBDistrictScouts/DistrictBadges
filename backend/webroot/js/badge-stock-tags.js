(function () {
    'use strict';

    var columns = document.querySelectorAll('.badge-stock-tile__tag-column');

    var fitTags = function (column) {
        var tags = Array.from(column.querySelectorAll('[data-stock-tag]'));
        var overflow = column.querySelector('[data-stock-tag-overflow]');
        if (!overflow) {
            return;
        }

        tags.forEach(function (tag) {
            tag.hidden = false;
        });
        overflow.hidden = true;

        var gap = parseFloat(getComputedStyle(column).columnGap) || 0;
        var totalWidth = tags.reduce(function (width, tag, index) {
            return width + tag.offsetWidth + (index === 0 ? 0 : gap);
        }, 0);
        if (totalWidth <= column.clientWidth) {
            return;
        }

        overflow.hidden = false;
        var availableWidth = column.clientWidth - overflow.offsetWidth - gap;
        var usedWidth = 0;
        tags.forEach(function (tag, index) {
            var nextWidth = tag.offsetWidth + (index === 0 ? 0 : gap);
            if (usedWidth + nextWidth <= availableWidth) {
                usedWidth += nextWidth;
            } else {
                tag.hidden = true;
            }
        });
    };

    var fitAllTags = function () {
        columns.forEach(fitTags);
    };

    fitAllTags();
    if ('ResizeObserver' in window) {
        var observer = new ResizeObserver(fitAllTags);
        columns.forEach(function (column) {
            observer.observe(column);
        });
    } else {
        window.addEventListener('resize', fitAllTags);
    }
}());

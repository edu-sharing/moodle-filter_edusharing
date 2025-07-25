// This file is part of edu-sharing created by metaVentis GmbH — http://metaventis.com
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['jquery'], function($) {

    return {
        init: function() {

            // eslint-disable-next-line @babel/no-unused-expressions
            !(function() {
                /**
                 * @param {number} a
                 * @param {number} b
                 */
                function a(a, b) {
                    var c = void 0 !== window.pageYOffset ? window.pageYOffset : (document.documentElement ||
                            document.body.parentNode || document.body).scrollTop,
                        d = document.documentElement.clientHeight,
                        e = c + d;
                    b = b || 0;
                    var f = a.getBoundingClientRect();
                    if (0 === f.height) {
                        return !1;
                    }
                    var g = f.top + c - b,
                        h = f.bottom + c + b;
                    return h > c && e > g;
                }

                $.expr[":"]["near-viewport"] = function(b, c, d) {
                    var e = parseInt(d[3]) || 0;
                    return a(b, e);
                };
            }());

            $.ajaxSetup({cache: false});

            var videoFormat = 'webm';
            var v = document.createElement('video');
            if (v.canPlayType && v.canPlayType('video/mp4').replace(/no/, '')) {
                videoFormat = 'mp4';
            }

            /**
             * @param {object} esObject
             * @param {jQuery} wrapper
             */
            function renderEsObject(esObject, wrapper) {
                var url = esObject.attr("data-url") + '&videoFormat=' + videoFormat;
                if (typeof wrapper == 'undefined') {
                    var wrapper = esObject.parent();
                }
                $.get(url, function(data) {
                    wrapper.html('').append(data).css({display: 'none', height: 'auto', width: 'auto'}).fadeIn('slow', 'linear');
                    if (data.toLowerCase().indexOf('data-view="lock"') >= 0) {
                        setTimeout(function() {
                            renderEsObject(esObject, wrapper);
                        }, 1111);
                    }
                });
                esObject.removeAttr("data-type");
            }

            $("div[data-type='esObject']:near-viewport(400)").each(function() {
                renderEsObject($(this));
            });

            // fix in case the theme does scroll via div/local container and not via window viewport
            setInterval(function() {
                $("div[data-type='esObject']:near-viewport(400)").each(function() {
                    renderEsObject($(this));
                });}, 1000);

            $(window).scroll(function() {
                $("div[data-type='esObject']:near-viewport(400)").each(function() {
                    renderEsObject($(this));
                });
            });
        }
    };
});

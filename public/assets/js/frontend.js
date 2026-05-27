(function () {
  function sendClickMetric(el) {
    if (!el || !window.kontentainmentLists) return;

    var payload = new URLSearchParams();
    payload.append("action", "charts_track_click");
    payload.append("nonce", window.kontentainmentLists.nonce || "");
    payload.append("page_type", el.getAttribute("data-page-type") || "");
    payload.append("object_type", el.getAttribute("data-object-type") || "");
    payload.append("object_id", el.getAttribute("data-object-id") || "0");
    payload.append("slug", el.getAttribute("data-slug") || "");

    if (navigator.sendBeacon) {
      var blob = new Blob([payload.toString()], { type: "application/x-www-form-urlencoded; charset=UTF-8" });
      navigator.sendBeacon(window.kontentainmentLists.ajaxUrl, blob);
      return;
    }

    fetch(window.kontentainmentLists.ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: payload.toString(),
      credentials: "same-origin"
    }).catch(function () {});
  }

  document.addEventListener("click", function (event) {
    var el = event.target.closest(".charts-track-click");
    if (el) {
      sendClickMetric(el);
    }

    var copy = event.target.closest("[data-share-copy]");
    if (copy && window.navigator && navigator.clipboard) {
      event.preventDefault();
      navigator.clipboard.writeText(copy.getAttribute("data-share-copy")).catch(function () {});
      copy.setAttribute("data-copied", "1");
      copy.textContent = (window.kontentainmentLists && window.kontentainmentLists.copied) || "تم النسخ";
      setTimeout(function () {
        copy.textContent = copy.getAttribute("data-share-label") || (window.kontentainmentLists && window.kontentainmentLists.copy) || "نسخ النص";
      }, 1600);
    }
  });
})();

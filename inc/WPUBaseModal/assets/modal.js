(function() {
    'use strict';
    var modalClass = "wpubasemodalplaceholder-modal";
    var closeClass = "wpubasemodalplaceholder-modal-close";

    function close(modal) {
        if (modal) {
            modal.style.display = "none";
        }
    }
    document.addEventListener("click", function(e) {
        if (e.target.classList.contains(closeClass)) {
            close(e.target.closest("." + modalClass));
        } else if (e.target.classList.contains(modalClass)) {
            close(e.target);
        }
    });
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            document.querySelectorAll("." + modalClass).forEach(close);
        } else if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains(closeClass)) {
            e.preventDefault();
            close(e.target.closest("." + modalClass));
        }
    });
})();

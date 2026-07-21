window.refreshAppIcons = function (root) {
    if (!window.lucide) {
        return;
    }

    if (root) {
        lucide.createIcons({ root });
        return;
    }

    lucide.createIcons();
};

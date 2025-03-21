(function() {
    const developerTools = document.getElementById('developerTools');
    let shiftCount = 0;
    let shiftTimer;

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Shift') {
            shiftCount++;
            clearTimeout(shiftTimer);

            shiftTimer = setTimeout(function() {
                shiftCount = 0;
            }, 2000);

            if (shiftCount >= 5) {
                developerTools.classList.toggle('show-dev-tools');
                shiftCount = 0;
            }
        }
    });
})();
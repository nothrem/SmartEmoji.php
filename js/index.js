/**
 * This file, when included in HTML page, will automatically add emoji button to marked inputs, text-fields or content-editable elements.
 * To mark an element, add class .smart-emoji-input or data attribute data-smart-emoji=input
 */
(function(window) {
    var
        inputSelector = '.smart-emoji-input, [data-smart-emoji=input]'
        init = function() {
            var cls = window.document.createElement('SCRIPT');

            //First define onload method to be able to always catch it
            cls.onload = function() {
                var emoji = new SmartEmoji(inputSelector, 'auto');
            };
            //Second append element into HTML body (otherwise some browsers may not load it)
            window.document.body.appendChild(cls);
            //Last but not least define type and source of the file
            cls.type = "text/javascript"
            cls.src = "SmartEmoji.js";
        };

    window.document.addEventListener('load', init);
})(this);

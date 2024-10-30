/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


var LiveHelpSettings = {};
LiveHelpSettings.server = 'ilockout.com';
LiveHelpSettings.embedded = true;
(function($) {
    $(function() {
        $(window).ready(function() {
            // JavaScript
            LiveHelpSettings.server = LiveHelpSettings.server.replace(/[a-z][a-z0-9+\-.]*:\/\/|\/livehelp\/*(\/|[a-z0-9\-._~%!$&'()*+,;=:@\/]*(?![a-z0-9\-._~%!$&'()*+,;=:@]))|\/*$/g, '');

            var LiveHelp = document.createElement('script');
            LiveHelp.type = 'text/javascript';
            LiveHelp.async = true;
            LiveHelp.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + LiveHelpSettings.server + '/livehelp/scripts/jquery.livehelpwp.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(LiveHelp, s);
        });
    });
})(jQuery);

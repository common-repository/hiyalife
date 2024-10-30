(function() {
    tinymce.create('tinymce.plugins.HiyalifeShortcodeMargenn', {
        
        init : function(ed, url) {        
        	var popUpURL = url + '/hiyalife-shortcode-tinymce.html';
        
			ed.addCommand('HiyalifeShortcodePopupMargenn', function() {
				ed.windowManager.open({
					url : popUpURL,
					width : 800,
					height : 600, 
					inline : 1
				});
			});

			ed.addButton('HiyalifeShortcodePopup', {
				title : 'Hiyalife Lifeline Shortcode',
				image : url + '/hiyalife-shortcode-button.png',
				cmd : 'HiyalifeShortcodePopupMargenn'
			});
		}
    });
    tinymce.PluginManager.add('HiyalifeShortcodeMargenn', tinymce.plugins.HiyalifeShortcodeMargenn);
}());
(function($) {
	
	$.fn.TicketMDE = function(options) {
		
		var defaults = {
				locale: 'en_GB',
				token: ''
		};
		
		options = $.extend(defaults, options);
		
		this.each(function() {

			var id = $(this).attr('id');
			
			$(this).markdown({
				footer: '<div id="' + id + '-footer" class="markdown-editor-status"></div>',
				autofocus: false,
				savable: false,
				resize: 'vertical',
				iconlibrary: 'fa',
				language: options.locale,
				onPreview: function(e){

					var originalContent = e.getContent(), parsedContent;

					jQuery.ajax({
						url: '/admin/supporttickets.php',
						async: false,
						data: {token: options.token, action: 'parseMarkdown', content: originalContent},
						success: function (data) {
							parsedContent = data;
						}
					});

					return parsedContent.body ? parsedContent.body : '';
				},
				additionalButtons: [
					[{
						name: "groupCustom",
						data: [{
							name: "cmdHelp",
							title: "Help",
							hotkey: "Ctrl+F1",
							btnClass: "btn open-modal",
							href: "supporttickets.php?action=markdown",
							icon: {
								glyph: 'glyphicons glyphicons-question-sign',
								fa: 'fa fa-question-circle',
								'fa-3': 'icon-question-sign'
							},
							callback: function(){},
							additionalAttr: [
								{
									name: 'data-modal-title',
									value: 'Markdown Guide'
								},
								{
									name: 'data-modal-size',
									value: 'modal-lg'
								}
							]
						}]
					}]
				]
			});
			
			return this;
		});
	};

})(jQuery);
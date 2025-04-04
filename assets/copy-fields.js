
(function($) {
	const fieldSelector = '.acf-field-textarea textarea, .acf-field-text input, .acf-field-range input'
	const buttonsSelector = '.acf-field-button-group:not([data-name="enabled"]) .acf-button-group'

	const copyToClipboard = (data) => {
		const textArea = document.createElement('textarea');
		textArea.value = data;
		document.body.appendChild(textArea);
		textArea.select();
		document.execCommand('Copy');
		textArea.remove();
	};

	function applyBorder ( target, tempColor, duration = 1500 ) {
		const oldBorder = target.css('outline');
		target.css('outline', tempColor)
		setTimeout(function () {
			target.css('outline', oldBorder)
		}, duration)
	}

	function checkParentValidity () {
		// if ( $(this).parents(".acf-clone").length )
		// 	return false;
		const $translatableParent = $(this).parents(".BareFields_translatedField").eq(0)
		if ( $translatableParent.css("display") === "none" )
			return false
		return true;
	}

	const collectData = () => {
		const fieldsData = {
			title: $('#title').val(),
			texts: [],
			buttons: [],
		};
		$(fieldSelector).filter(checkParentValidity).each(function() {
			const target = $(this)
			applyBorder(target, '2px dashed red')
			fieldsData.texts.push(target.val());
		});
		$(buttonsSelector).filter(checkParentValidity).each(function () {
			const target = $(this)
			applyBorder(target, '2px dashed red');
			fieldsData.buttons.push(
				target.children('label.selected').index()
			);
		});
		return JSON.stringify(fieldsData);
	};

	const pasteFromClipboard = () => {
		navigator.clipboard.readText().then((text) => {
			const fieldsData = JSON.parse(text);
			$('#title').val( fieldsData.title ).trigger('change');
			let indexOffset = 0
			$( fieldSelector ).filter(checkParentValidity).each(function(index) {
				const target = $(this)
				applyBorder(target, '2px dashed green');
				target.val(fieldsData.texts[index + indexOffset]).trigger('change');
			});
			indexOffset = 0
			$( buttonsSelector ).filter(checkParentValidity).each(function(index) {
				const target = $(this)
				if ( !checkParentValidity(target) )
					return;
				applyBorder(target, '2px dashed green');
				const value = fieldsData.buttons[index]
				target.children(`label:eq(${value})`)[0]?.click();
			});
		});
	};

	const buttonStyle = 'margin:0;padding:4px 8px;font-size:14px;line-height:1.4;border:none;border-radius:4px;background-color:#f7f7f7;cursor:pointer;';
	const buttonsDivStyle = 'display:flex;gap:10px;position:fixed;bottom:10px;right:10px;z-index:9999;';

	const buttonsDiv = $('<div>', { style: buttonsDivStyle }).appendTo('body');

	const pasteButton = $('<button>', {
		text: 'Paste Fields',
		style: buttonStyle,
		click: function() {
			pasteFromClipboard();
			$(this).css('background-color', '#ffcdd2');
			setTimeout(() => $(this).css('background-color', '#f7f7f7'), 1000);
		}
	}).appendTo(buttonsDiv);

	const copyButton = $('<button>', {
		text: 'Copy Fields',
		style: buttonStyle,
		click: function() {
			const data = collectData();
			copyToClipboard(data);
			$(this).css('background-color', '#c8e6c9');
			setTimeout(() => $(this).css('background-color', '#f7f7f7'), 1000);
		}
	}).appendTo(buttonsDiv);
})(jQuery);


const hasACF = typeof acf === "object"

jQuery(function ($) {
	if ( typeof window.__BareFields !== "object" )
		return
	const barefieldsConfig = window.__BareFields
	const { locales, isListing } = barefieldsConfig
	// In listing, we simply follow the links
	if ( isListing ) return
	const localeKeys = Object.keys(locales)
	const localeSelector = document.querySelector(".BareFields_localeSelector")
	// Target links on locale selector
  const localeLinks = [...localeSelector.querySelectorAll("a")]
	barefieldsConfig.setErrorsOnLocale = function (locale, totalErrors = 0) {
		localeLinks.forEach( link => {
			if ( locale !== link.dataset.locale ) return
			const hasErrors = totalErrors > 0
			link.classList.toggle("errored", hasErrors)
			link.querySelector("span").innerText = hasErrors ? ` (${totalErrors})` : null
		})
	}
	// Select a locale
  function selectLocale ( newLocale ) {
		barefieldsConfig.currentLocale = newLocale
		localeLinks.forEach( link => {
    	const locale = link.dataset.locale
      link.classList.toggle("selected", locale === newLocale)
		})
		localeKeys.forEach( locale => {
			document.body.classList.toggle(`BareFields_body__${locale}`, locale === newLocale)
		})
  }
	function registerLocaleSelectorLinks ( localeLinks ) {
		// Listen to locale links on locale selector
		// Bypass original href change and update in js runtime instead
		localeLinks.forEach( link => {
			const locale = link.dataset.locale
			link.addEventListener("click", event => {
				event.preventDefault()
				if ( locale !== barefieldsConfig.currentLocale ) {
					selectLocale(locale)
					const href = event.target.getAttribute('href')
					// Fetch href silently to save the new selected locale in session
					fetch( href )
				}
			})
		})
	}
	registerLocaleSelectorLinks( localeLinks )
	// Select next locale when clicking on locale marker
	let clonedLocaleSelector
	let cloneFinished = false
	const $document = $(document)
	function removeClonedLocaleSelector () {
		if ( !clonedLocaleSelector ) return
		clonedLocaleSelector.remove()
		clonedLocaleSelector = null
	}
	$document.on("keyup", event => {
		if ( event.originalEvent.key === "Escape" && clonedLocaleSelector ) {
			event.preventDefault()
			removeClonedLocaleSelector()
		}
	})
	$document.on("click", () => {
		if ( !cloneFinished ) return
		removeClonedLocaleSelector()
	})
	$document.on('click', ".BareFields_locale", function(event) {
		cloneFinished = false
		removeClonedLocaleSelector();
		const { pageX, pageY } = event.originalEvent;
		clonedLocaleSelector = localeSelector.cloneNode(true)
		clonedLocaleSelector.style.position = "absolute";
		clonedLocaleSelector.style.left = pageX + "px";
		clonedLocaleSelector.style.top = pageY + "px";
		clonedLocaleSelector.style.zIndex = 9999;
		document.body.appendChild( clonedLocaleSelector )
		registerLocaleSelectorLinks([...clonedLocaleSelector.querySelectorAll("a")])
		setTimeout( () => { cloneFinished = true }, 100)
	})
})

// Hook acf validation before sending post for saving
// We will disable all translated fields that are in a not selected locale
// This allows to save a post with required fields that are in another locale
hasACF && acf.add_filter('validation_complete', function( json, $form, validator ) {
	// Always validate if API is not found
	if ( typeof window.__BareFields !== "object" )
		return json
	// Grab config
	const barefieldsConfig = window.__BareFields
	const { locales, isListing } = barefieldsConfig
	// Continue only when editing a post
	if ( isListing )
		return json
	const localeKeys = Object.keys(locales)
	// Reset errors on locale selector
	localeKeys.forEach( locale => barefieldsConfig.setErrorsOnLocale(locale, 0) )
	// No error, simply validate,
	// it allows passing with invalid translated values in disabled locales
	if ( !json.errors || (Array.isArray(json.errors) && json.errors.length === 0) ) {
		validator.clearErrors()
	}
	// We have some errors
	else if ( Array.isArray(json.errors) && json.errors.length > 0 ) {
		// Grab all fields and parse all errors to find associated fields
		const allFields = acf.getFields({ parent: $form })
		const erroredLocales = {}
		localeKeys.forEach( locale => erroredLocales[locale] = 0 )
		json.errors.map( error => {
			const { input } = error
			if ( !input ) return
			// Get all locales that contains errors
			allFields.filter( field => field.getInputName() === input )
				.filter( field => localeKeys.indexOf(field.data.name) !== -1  )
				.forEach( field => ++erroredLocales[field.data.name] )
		})
		// Set a warning color on the locale selector
		localeKeys.forEach( locale => {
			barefieldsConfig.setErrorsOnLocale(locale, erroredLocales[locale])
		})
	}
	return json
})

// When changing available locale in multilang box
// Send an ajax request to save it directly without having to save post
// We have to do this to avoid being unable to uncheck (a locale) and save with a missing field
jQuery(function($) {
	if ( !hasACF ) return
	const localeInputsSelector = 'input[name="acf[locales][]"]'
  $(document).on('change', localeInputsSelector, function() {
    const postId = $('#post_ID').val()
		const localesInputs = [...document.querySelectorAll(localeInputsSelector)]
		const setDisabled = value => localesInputs.map( el => {
			value ? el.setAttribute('disabled', true) : el.removeAttribute('disabled')
		})
		const selectedLocales = localesInputs.filter( el => el.checked ).map( el => el.value )
		setDisabled( true )
    $.post(acfAjax.ajax_url, {
      action: 'acf_save_locales',
      post_id: postId,
      locales: selectedLocales
    }).done( () => setDisabled( null ) )
  })
})

// Patch link href with locale on the fly when clicked
jQuery(function ($) {
	if ( typeof window.__BareFields !== "object" )
		return
	const barefieldsConfig = window.__BareFields
	$("span#sample-permalink a, a#sample-permalink").click((e) => {
		// e.preventDefault()
		let href = $(e.currentTarget).attr("href")
		href = href.split("?")[0]
		const { currentLocale, locales } = barefieldsConfig
		const localeKeys = Object.keys(locales)
		href += "?locale="+(currentLocale === "all" ? localeKeys[0] : currentLocale)
		$(e.currentTarget).attr("href", href).attr("target", "_blank")
	})
})

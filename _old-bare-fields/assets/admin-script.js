
function patchFlexibleBehavior ( $ ) {
  // Invert flexible layouts collapsed state when we click on flexible content title
  var $flexibleLabel = $('.acf-field-flexible-content > .acf-label > label');
  $flexibleLabel.each(function (i, el) {
    var opened = true;
    var collapsedClass = '-collapsed';
    $(el)
      .text( $(el).text() + " ↕️" )
      .on('click', function (e) {
        var $layouts = $(e.currentTarget).parent().parent().find('.values > .layout');
        opened = !opened;
        opened ? $layouts.removeClass(collapsedClass) : $layouts.addClass(collapsedClass);
      })
  })
}

jQuery(document).ready( function ($) {
  if (!window._customMetaboxBehavior) return;
  patchFlexibleBehavior($)
});



// TRANSLATIONS
//
// jQuery(document).ready( function ($) {
//   const post = document.getElementById("post")
//   if ( !post )
//     return
//   const selectorContainer = document.createElement('div')
//   const locales = ["en", "fr"]
//   selectorContainer.setAttribute("class", "woolkitTranslateSelector")
//   selectorContainer.innerHTML = `<div class="acf-button-group">
//     ${locales.map( locale => `<label tabindex="0" data-locale="${locale}">${locale}</label>`).join("")}
//   </div>`
//   const labels = selectorContainer.querySelectorAll("label")
//   const labelsByLocale = {}
//   let currentLocale = null
//   const allFields = document.querySelectorAll(".acf-field")
//   const translatableFields = [...allFields].filter( f => f.dataset.name?.startsWith("$$$"))
//   const translatableFieldsByLocales = {}
//   locales.forEach( locale => { translatableFieldsByLocales[ locale ] = [] })
//   translatableFields.forEach( field => {
//     const fieldLocale = field.dataset.name?.split("$$$")[1]?.split("___")[0]
//     translatableFieldsByLocales[ fieldLocale ].push( field )
//   })
//   // console.log( translatableFields );
//
//   function selectLocale ( locale ) {
//     if ( locale === currentLocale )
//       return
//     currentLocale = locale
//     localStorage.setItem("_woolkitLocale", locale)
//     Object.keys(labelsByLocale).map( l => {
//       labelsByLocale[l].classList.toggle("selected", l === locale)
//       translatableFieldsByLocales[l].forEach(
//         // f => f.classList.toggle("translationHidden", l !== locale)
//         f => f.toggleAttribute("hidden", l !== locale)
//       )
//     })
//   }
//   ;[...labels].forEach( label => {
//     const locale = label.dataset.locale
//     labelsByLocale[ locale ] = label
//     label.addEventListener("click", event => {
//       selectLocale(locale)
//     })
//   })
//   let defaultLocale = localStorage.getItem("_woolkitLocale") ?? locales[0]
//   if ( locales.indexOf(defaultLocale) === -1 )
//     defaultLocale = locales[0]
//   selectLocale(defaultLocale)
//   // console.log( post );
//   const wpContent = document.getElementById("wpbody-content")
//   wpContent.prepend( selectorContainer )
// })

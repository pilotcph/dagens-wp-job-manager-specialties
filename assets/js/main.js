// (function($) {
//   'use strict';

//   var jobspecialties = {
//     cache: {
//       $document: $(document),
//       $window: $(window),
//     },

//     init: function() {
//       this.bindEvents();
//     },

//     bindEvents: function() {
//       var self = this;

//       this.cache.$document.on( 'ready', function() {
//         self.$forms = $( '.search_jobs' );

//         self.addSubmission();
//         self.addspecialties();
//         self.updateResults();
//         self.resetResults();
//       });
//     },

//     addSubmission: function() {
//       $( '#search_specialty' ).chosen({
//         search_contains: true
//       });
//     },

//     addspecialties: function() {
//       this.$forms.each(function(i, el) {
//         var $specialtiess = $(el).find( 'select.search_specialty' );

//         if ( ! $specialtiess.length ) {
//           return;
//         }

//         var locationn = $(el).find( '.search_location' );
//         locationn.html( '' );
//         locationn.removeClass( 'search_location' ).addClass( 'search_specialty' );

//         $specialtiess.detach().appendTo(locationn);
//         $specialtiess.chosen({
//           search_contains: true
//         });
//       });
//     },

//     updateResults: function() {
//       this.$forms.each(function(i, el) {
//         var specialty = $(this).find( '#search_specialty' );

//         specialty.on( 'change', function() {
//           var target = $(this).closest( 'div.job_listings' );

//           target.trigger( 'update_results', [ 1, false ] );
//         });
//       });
//     },

//     resetResults: function() {
//       var self = this;

//       $( '.job_listings' ).on( 'reset', function() {
//         self.$forms.each(function(i, el) {
//           var $specialties = $(el).find( 'select.search_specialty' );
//           $specialties.val(0).trigger( 'change' ).trigger( 'chosen:updated' );
//         });
//       });
//     }
//   };

//   jobspecialties.init();

// })(jQuery);

function updateListings(){
   jQuery('#search_specialty').chosen({
    search_contains: true
  });

  jQuery('#search_specialty').change(function(){
    var target = jQuery(this).closest( 'div.job_listings' );
    target.trigger( 'update_results', [ 1, false ] );
  });
}

function dagens_update_job_manager_specialties_ui(){
  jQuery('select').each(function(){
    jQuery(this).find('option:first-child').prop('selected', true).end().trigger('chosen:updated');
  });
}

jQuery(document).ready(function(){
  updateListings();
});

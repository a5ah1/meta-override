(function ($) {
  'use strict';

  $(document).ready(function () {
    var customUploader;

    function updateCharCount(input) {
      var charCount = $(input).val().length;
      $(input).siblings('.mo-char-count').text(charCount + ' characters');
    }

    $('#meta_title, #meta_description').on('input', function () {
      updateCharCount(this);
    });

    $('#meta_title, #meta_description').each(function () {
      updateCharCount(this);
    });

    $(document).on('click', '.mo-image-picker-button', function (e) {
      e.preventDefault();

      var buttonId = $(this).attr('id');
      var inputId = buttonId.replace(/_button$/, '');
      var imageIdInputId = inputId + '_id';

      // Always create a new media uploader to ensure correct button context
      customUploader = wp.media({
        title: 'Choose Image',
        button: {
          text: 'Use this image'
        },
        multiple: false
      });

      // Remove previous event handlers to prevent duplication
      customUploader.off('select');

      customUploader.on('select', function () {
        var attachment = customUploader.state().get('selection').first().toJSON();
        $('#' + inputId).val(attachment.url);
        $('#' + imageIdInputId).val(attachment.id);

        // Trigger change event to update Twitter image if sync is enabled
        $('#' + inputId).trigger('change');
      });

      customUploader.open();
    });

    function toggleOgImageFeatured() {
      var useFeatured = $('#og_image_use_featured').is(':checked');
      var $ogImage = $('#og_image');
      var $ogImageId = $('#og_image_id');
      var $ogImageButton = $('#og_image_button');

      if (useFeatured) {
        // Store current custom values before overwriting
        if (!$ogImage.data('customStored')) {
          $ogImage.data('customUrl', $ogImage.val());
          $ogImageId.data('customId', $ogImageId.val());
          $ogImage.data('customStored', true);
        }

        $ogImage.val(metaOverrideData.featuredImageUrl);
        $ogImageId.val('');
        $ogImage.prop('disabled', true);
        $ogImageId.prop('disabled', true);
        $ogImageButton.prop('disabled', true);
      } else {
        // Restore custom values
        if ($ogImage.data('customStored')) {
          $ogImage.val($ogImage.data('customUrl'));
          $ogImageId.val($ogImageId.data('customId'));
          $ogImage.data('customStored', false);
        }

        $ogImage.prop('disabled', false);
        $ogImageId.prop('disabled', false);
        $ogImageButton.prop('disabled', false);
      }

      // Trigger change to cascade to Twitter sync
      $ogImage.trigger('change');
    }

    function toggleTwitterFields() {
      var titleSameAsOg = $('#twitter_title_same_as_og').is(':checked');
      var descriptionSameAsOg = $('#twitter_description_same_as_og').is(':checked');
      var imageSameAsOg = $('#twitter_image_same_as_og').is(':checked');

      $('#twitter_title').prop('disabled', titleSameAsOg);
      $('#twitter_description').prop('disabled', descriptionSameAsOg);
      $('#twitter_image').prop('disabled', imageSameAsOg);
      $('#twitter_image_button').prop('disabled', imageSameAsOg);

      if (titleSameAsOg) {
        $('#twitter_title').val($('#og_title').val());
      }
      if (descriptionSameAsOg) {
        $('#twitter_description').val($('#og_description').val());
      }
      if (imageSameAsOg) {
        $('#twitter_image').val($('#og_image').val());
        $('#twitter_image_id').val($('#og_image_id').val());
      }
    }

    $('#og_image_use_featured').change(toggleOgImageFeatured);
    $('#twitter_title_same_as_og, #twitter_description_same_as_og, #twitter_image_same_as_og').change(toggleTwitterFields);
    $('#og_title, #og_description').on('input', toggleTwitterFields);
    $('#og_image').on('input change', toggleTwitterFields);

    toggleOgImageFeatured();
    toggleTwitterFields();
  });

})(jQuery);
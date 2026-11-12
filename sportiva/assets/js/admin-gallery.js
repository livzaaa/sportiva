/**
 * SPORTIVA yönetim paneli: salon / tesis olanağı galeri seçici (WordPress Medya Kütüphanesi).
 */
(function ($) {
	'use strict';

	$(function () {
		$('[data-sp-gallery]').each(function () {
			var $box = $(this);
			var $ids = $box.find('[data-sp-ids]');
			var $list = $box.find('.sp-gallery__list');
			var frame = null;
			var isDoc = $box.attr('data-sp-type') === 'doc';

			function sync() {
				var ids = [];
				$list.children('li').each(function () { ids.push($(this).attr('data-id')); });
				$ids.val(ids.join(','));
			}

			$list.sortable({ update: sync });

			$box.on('click', '.sp-gallery__remove', function () {
				$(this).closest('li').remove();
				sync();
			});

			$box.on('click', '[data-sp-add]', function (e) {
				e.preventDefault();
				if (!frame) {
					frame = wp.media({
						title: isDoc ? 'Sertifika ve belgeler' : 'Galeri fotoğrafları',
						button: { text: isDoc ? 'Ekle' : 'Galeriye ekle' },
						library: { type: isDoc ? ['image', 'application/pdf'] : 'image' },
						multiple: 'add'
					});
					frame.on('select', function () {
						frame.state().get('selection').each(function (att) {
							var a = att.toJSON();
							if ($list.children('li[data-id="' + a.id + '"]').length) { return; }
							var $li = $('<li>').attr('data-id', a.id);
							if (a.type === 'image') {
								var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
								$li.append($('<img>').attr({ src: url, alt: '' }));
							} else if (a.mime === 'application/pdf') {
								$li.append($('<span class="sp-gallery__file">').text('PDF ' + (a.filename || '')));
							} else {
								return; // Yalnızca görsel ve PDF kabul edilir.
							}
							$li.append($('<button type="button" class="sp-gallery__remove" aria-label="Fotoğrafı kaldır">&times;</button>'));
							$list.append($li);
						});
						sync();
					});
				}
				frame.open();
			});
		});
	});
})(jQuery);

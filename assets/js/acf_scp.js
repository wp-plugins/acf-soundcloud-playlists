/**
 * Edit Panel (on Post) JS
 * Additional JS scripts to assist the admin interface.
 *
 */

( function($) {
	
	var browse_class = '.browse-container-scplaylist';
	
	scp = {

		show_browse: function( $this ) {

			var tb_coreShow = tb_show,
				href = $this.attr('href');
			
			// run thickbox manually.
			tb_show( 'Browse Playlist', href, false );
			
			// vars
			var $browse = $('#TB_window').find( browse_class ),
				$tr = $this.closest('.acf-field'),
				$input	= $this.closest('.browse').siblings('.acf-hidden'),
				n	= $this.attr('data-scp-name'),
				k	= $tr.attr('data-key');

			// console.log( $.parseJSON(v) );
			// initialize browse

			$browse.attr({
			  'data-scp-name': n,
			  'data-scp-key': k,
			});

			$browse.data('load-button', $this);

			// bail out if scp browse isn't authenticated yet
			if( $browse.attr('data-scp-ready') == 0 )
				
				return;

			var id = $input.find('input[data-name="id"]').val(),
				ajax_data = {
					action 		  : 'acf/scp/load_playlists',
					name 		  : n,
					track		  : id,
				};

			if( $browse.find('.browse-content > .playlist').length < 1 ) {

				// load playlist for the first time
				scp.load_playlists( $browse, ajax_data );
			
			} else {

				// change selected playlist.
				scp.change_playlist( $browse, id );

			}

		},

		load_playlists: function( $browse, ajax ){

			var $playlists_div = $browse.children('.browse-content');

			// add loading
			var $scploading = $('<div class="scp-loading media-iframe"><span class="spinner"></span></div>');


			// abort XHR if this field is already loading AJAX data
			if( $browse.data('scp-xhr') ) {
			
				$browse.data('scp-xhr').abort();
				
			}


			$browse.before( $scploading );


			// ajax data
			ajax.nonce	= acf.o.nonce;
			ajax.post	= acf.o.post_id;
			
			// debug
			// console.log(ajax);

			// ajax request
			var scp_xhr = $.ajax({
				url: acf.o.ajaxurl,
				data: ajax,
				type: 'post',
				dataType: 'html',
				success: function( html ){
					
					// console.log(html);
					
					// bail early if no html @cc acf/group.js
					if( !html ) {
					
						return;
						
					}

					// vars
					var $selections = $(html);
					
					// replace
					$playlists_div.append( $selections );

					// load selectize
					$browse.find('.playlist .results').selectize({

					  sortField: 'text',
					  hideSelected: true,
					  onChange: function(value) {
					    //var playlist_label =  $('.results').next().find('.selectize-input .item').text(); 
					    
					    if ( $('.results option[selected="selected"]').val().length > 1 )
					    
					    	$('.sc-set-playlist').removeAttr('disabled');
					    
					    else 

					    	$('.sc-set-playlist').attr('disabled','disabled');
					    
					  },

					});
					
				},
				complete : function(){
					
					// remove loading 
					$scploading.remove();
					
				}
			});

			$browse.data('scp-xhr', scp_xhr);

		}, // end load_playlists

		change_playlist: function( $browse, id ) {
			
			// initialize the selectize control
			var selectize = $browse.find('.playlist .results')[0].selectize;

			selectize.setValue(id);

		},

		set_playlist: function( $this ) {

			var $browse = $this.closest( browse_class ),
				$playlist = $browse.find('.playlist'),
				$selections = $playlist.find('.results'),
				b = $browse.data('load-button'), // load button
				$h = $(b[0]).closest('.browse').siblings('.acf-hidden'), // hidden inputs
				metadata = $.parseJSON( $playlist.attr('data-scp-metadata') ),

				info = {
					id: $selections.children('option[selected="selected"]').val(),
				};
				$.extend( info, metadata[info.id] );

			console.log($h);

			$h.find('input').each( function(index) {
				
				var attr = $(this).attr('data-name');

				console.log(attr);
				console.log(info[attr]);

				$(this).val(info[attr]);

			});

			// $h.children('input[data-name="id"]').val(id);
			// $h.children('input[data-name="title"]').val(title);
			// $h.children('input[data-name="count"]').val(count);

			// var play_sel = [ id, title, count ];

			//$button.attr( 'data-scp-track', JSON.stringify(play_sel) );
			$(b[0]).find( 'span.playlist-label' ).html( info.title + '&nbsp;<span class="track-count">'+ info.count +'&nbsp;tracks </span>' );

			tb_remove();

		},

		connect: function( $this ) {
			var $auth = $this.closest('.auth'),
				$browse  = $auth.closest( browse_class );

			// SoundCloud SDK initialize
			SC.initialize({
              client_id: acf.scp.client_app_key,
              access_token : '',
              redirect_uri: acf.scp.html_callback,
            });

			// Auth begins
			SC.connect( function() {

				// ajax data
				var ajax_data = {
						action 		: 'acf/scp/register_token',
						name 		: $browse.attr('data-scp-name'),
						tokey  		: SC.accessToken().toString(),
					};

				// verify if token is not available and add message then return.	
				if( ajax_data.tokey.length < 1 ) {

					$auth.prepend('<div class="scp_says">Bummer! There seems to be a miscommunication with me and SoundCloud</div>'); 
					return;

				}

				// remove auth div
				$auth.remove();

				// change scp browse to ready to load!
				$browse.attr('data-scp-ready', 1);

				// show disconnect link
				$browse.find('.go-disconnect').removeClass('hidden-by-conditional-logic');

				scp.load_playlists( $browse, ajax_data  );

			// Auth ends
			});
		},

	};


	// When for the first time to request an AUTH to connect with SoundCloud
	$(document).ready(function () {
		
		var self = this;

		this.$playlist = $('.scp-active');
		this.$browse = $( browse_class );

		this.$browse.on( 'click', '.js_go-connect', function( e ) {

			e.preventDefault();

			scp.connect( $(this) );

		});

		this.$playlist.on( 'click', '.js_load-button', function( e ) {
			
			e.preventDefault();

			scp.show_browse( $(this) );

		});

		this.$browse.on( 'click', '.js_set-playlist', function( e ) {
              
			e.preventDefault();

			if( typeof $(this).attr('disabled') != 'undefined' )
				return;

			scp.set_playlist( $(this) );


        });

	});
	

})(jQuery);

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define(['jquery'], function($) {
    var more_list = [];
    var terms = null;
    return {
        initialise: function ($params) {

            var $modal = $('<div class="modal fade" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">' + M.str.block_selfenrollment.more_title + '</h4></div><div class="modal-body"></div></div></div></div>');

            $('.block_selfenrollment_table .icon-more').on('click', function() {

                var $this = $(this);
                var offerid = $this.attr('offerid');

                if (more_list[offerid]) {
                    $modal.find('.modal-body').html(more_list[offerid]);
                    $modal.modal();
                }
                else {
                    $.get('more.php', { 'offerid': offerid }, function( data ) {

                        var $tpl = $($('#block_selfenrollment_offer_template_more').html());
                        $.each(data, function(index, value){
                            $tpl.find('#block_selfenrollment_offer_' + index).html(value);
                        });

                        more_list[offerid] = $tpl;
                        $('document').append($modal);
                        $modal.find('.modal-body').html($tpl);
                        $modal.modal();
                    }, 'json');
                }
            });
        },

        terms: function ($params) {

            var $modal = $('<div class="modal fade" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">' + M.str.block_selfenrollment.terms_title + '</h4></div><div class="modal-body"></div></div></div></div>');

            $('.block_selfenrollment_terms a').on('click', function(e) {

                e.preventDefault();
                var $this = $(this);

                if (terms) {
                    $modal.find('.modal-body').html(terms);
                    $modal.modal();
                }
                else {
                    $.get($this.attr('href'), function( data ) {

                        terms = data;
                        $('document').append($modal);
                        $modal.find('.modal-body').html(data);
                        $modal.modal();
                    });
                }
            });
        }
    };
});

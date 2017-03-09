
jQuery(document).ready(function($) {

    var migrateCF = $(WBWooEUTMigrationData.button_id);


    migrateCF.on('click', function (e) {
        e.preventDefault();
        do_update_db().then(function (result, textStatus, type) {
            var message = 'old fields found: '+result.found+'<br>' +
                'cf: '+result.cf+' <br> ' +
                'p.iva: '+result.piva+' <br> ' +
                'empty strings: '+result.empty_strings+' <br> ' +
                'unknown strings: '+result.unknown_strings+'<br>' +
                'new fields added: '+result.insertions+'<br>' +
                'old fields removed: '+result.removed;
            migrateCF
                .closest('.notice.notice-error')
                .removeClass('notice-error')
                .addClass('notice-success')
                .html('<p>'+message+'</p>')
            ;
        });
    });


    function do_update_db(){
        // ajax call for wordpress
        return $.ajax(WBWooEUTMigrationData.ajax_url,{
            data: { action: WBWooEUTMigrationData.pluginName+'_update_db_fields' },
            dataType: 'json',
            method: 'POST'
        })
            .done(function(data, textStatus, jqXHR){
                console.log('db updating');
            })
            .fail(function(jqXHR, textStatus, errorThrown){
                alert(errorThrown);
            })
            .always(function(result, textStatus, type){
                return result;
            });
    }
});
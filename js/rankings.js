$(document).ready(function() { 
        $("#rankingdata").tablesorter( {
            widgets: ['zebra'],
            headers: { 6: {sorter: false} }
            });
         $("#pairingdata").tablesorter( {
            widgets: ['zebra'],
            headers: { 5: {sorter: false} }
        });
    });


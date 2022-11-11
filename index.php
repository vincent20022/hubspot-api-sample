<?php
/**
 * API for fetch a server page
 * more description later
 * @author Rondeo Balos
 */
header("Content-Type: application/json; charset=UTF-8");
define( 'SERVER_API', 'pat-na1-42eee5bf-fd79-499c-8d8d-23f508b2ce06' );

/* $params = array(
    '<server_page_id>' = array(
        '<client1_page_id>' => array(
            'client_api' = '<client1_api>'
        ),
        '<client2_page_id>' => array(
            'client_api' = '<client2_api>'
        )
    ),
); */
$params = array(
    '91161842493' => array(
        '91260048136' => array(
            'client_api' => 'pat-na1-9d1a2526-1799-401e-9551-343dddb5a7c0'
        )
    ),
);

/**
 * Fetch Data from the page using ID
 * @param string $server_page Server Page ID
 * @return array Page Data
 * @author Rondeo Balos
 */
function getPageData( $server_page ) {
    $url = 'https://api.hubapi.com/content/api/v2/pages/'. $server_page;

    // Fetch data from the server
    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'authorization: Bearer ' . SERVER_API ] );
    $json = curl_exec( $curl );
    curl_close( $curl );

    $array = json_decode( $json, true );

    return $array;
}

/**
 * Fetch data from a page that has been recently updated then update the client pages
 * @param string $server_page Server Page ID
 * @author Rondeo Balos
 */
function getPage( $server_page ) {
    global $params;
    
    // Fetch data from the server
    $array = getPageData( $server_page );

    // Format data that will be sent to the clients or remove unecessary data
    $data = array();
    /*$blacklists = [ // blacklist data with keys that we don't want to copy
        'analytics_page_id', 
        'id', 
        'author_user_id', 
        'current_live_domain', 
        'domain', 
        'folder_id', 
        'page_expiry_redirect_id', 
        'page_expiry_redirect_url', 
        'url' 
    ];
    foreach( $array as $key => $value ) {
        if( in_array( $key, $blacklists ) )
            continue;
        $data[ $key ] = $value;
    }*/
    $data[ 'slug' ] = $array[ 'slug' ];
    $data[ 'html_title' ] = $array[ 'html_title' ];
    $data[ 'layout_sections' ] = filterNotNull( $array[ 'layout_sections' ] );

    echo json_encode( $data );

    // send data to clients;
    foreach( $params[ $server_page ] as $client_page_id => $value ) {
        //$data[ 'dynamic_page_data_source_id' ] = $client_page_id;
        updatePage( $client_page_id, $value[ 'client_api' ], $data );
    }
}

function filterNotNull($array) {
    $array = array_map(function($item) {
        return is_array($item) ? filterNotNull($item) : $item;
    }, $array);
    return array_filter($array, function($item) {
        return $item !== "" && $item !== null && (!is_array($item) || count($item) > 0);
    });
}

/**
 * Update client pages with the server page's data
 * @param string $client_page Client's Page ID
 * @param string $client_api Client's API
 * @param array $data Array Data that will be sent to the client
 * @author Rondeo Balos
 */
function updatePage( $client_page, $client_api, $data ) {
    $url = 'https://api.hubapi.com/content/api/v2/pages/' . $client_page . '/buffer';
    //$url = 'https://api.hubapi.com/cms/v3/pages/site-pages/' . $client_page;

    $curl = curl_init( $url );
    //curl_setopt( $curl, CURLOPT_PUT, true );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PUT" );
    curl_setopt( $curl, CURLINFO_HEADER_OUT, TRUE );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'authorization: Bearer ' . $client_api, 'Content-Type: application/json', 'Accept: application/json' ] );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $data ) );
    $json = curl_exec( $curl );

    curl_close( $curl );

    savePage( $client_page, $client_api )
}

function savePage( $client_page, $client_api ) {
    $url = 'https://api.hubapi.com/content/api/v2/pages/' . $client_page . '/validate-buffer';

    $curl = curl_init( $url );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" );
    curl_setopt( $curl, CURLINFO_HEADER_OUT, TRUE );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'authorization: Bearer ' . $client_api, 'Content-Type: application/json', 'Accept: application/json' ] );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, array() );
    $json = curl_exec( $curl );

    curl_close( $curl );
}

getPage( '91161842493' );

// Webhook to update page
if( isset( $_GET[ "page_id" ] ) ) {
    getPage( $_GET[ "page_id" ] );
}

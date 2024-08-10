<?php
/*
Plugin Name: TechPlay Simple Weather Widget
Description: A simple weather widget that uses OpenWeatherMap API to display current weather
Version: 1.3
Author: NoxWon
*/

// 관리자 메뉴에 설정 페이지 추가
function simple_weather_widget_menu() {
    add_menu_page(
        'Simple Weather Widget Settings',
        'Weather Widget',
        'manage_options',
        'simple-weather-widget-settings',
        'simple_weather_widget_settings_page',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'simple_weather_widget_menu');

// 캐시삭제
function clear_weather_cache() {
    if ( 
        !isset($_POST['action']) || 
        $_POST['action'] !== 'clear_weather_cache' || 
        !wp_verify_nonce($_POST['_wpnonce'], 'clear_weather_cache_nonce') || 
        !current_user_can('manage_options') 
    ) {
        wp_die('잘못된 요청입니다.');
    }

    global $wpdb;
    $result = $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'weather_data_%' OR option_name LIKE '_transient_timeout_weather_data_%'");

    // 삭제된 캐시 개수 확인
    error_log("Deleted " . $result . " weather cache entries.");

    wp_redirect(admin_url('admin.php?page=simple-weather-widget-settings&settings-updated=true'));
    exit;
}

// 설정 페이지의 내용
function simple_weather_widget_settings_page() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div id="message" class="updated notice is-dismissible"><p>설정이 저장되었습니다.</p></div>';
    }
    ?>
    <div class="wrap">
        <h2>Simple Weather Widget Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('simple-weather-widget-settings-group');
            do_settings_sections('simple-weather-widget-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OpenWeatherMap API Key</th>
                    <td><input type="text" name="weather_api_key" value="<?php echo esc_attr(get_option('weather_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Timezone</th>
                    <td>
                        <select name="weather_timezone">
                            <?php echo wp_timezone_choice(get_option('weather_timezone', 'Asia/Seoul')); ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Default City</th>
                    <td><input type="text" name="weather_default_city" value="<?php echo esc_attr(get_option('weather_default_city')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Style Sheet</th>
                    <td>
                        <select name="weather_style_sheet">
                            <option value="1" <?php selected(get_option('weather_style_sheet'), '1'); ?>>Style 1 (Default)</option>
                            <option value="2" <?php selected(get_option('weather_style_sheet'), '2'); ?>>Style 2 (Custom)</option>
                            <option value="3" <?php selected(get_option('weather_style_sheet'), '3'); ?>>Style 3 (Predefine)</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
		<form method="post">
			<input type="hidden" name="action" value="clear_weather_cache">
			<?php wp_nonce_field('clear_weather_cache_nonce'); ?>
			<?php submit_button('캐시 삭제', 'secondary', 'clear_weather_cache_button'); ?>
		</form>
		<p>숏코드 사용 방법:</p>
        <ul>
            <li>[simple_weather_widget]: 기본 설정으로 날씨 정보 표시</li>
            <li>[simple_weather_widget city="London"]: 특정 도시의 날씨 정보 표시</li>
            <li>[simple_weather_widget show_sunrise="true" show_humidity="true"]: 추가 정보 표시</li>
        </ul>
    </div>
    <?php
}

// 설정 옵션 등록 및 관리
function simple_weather_widget_register_settings() {
    register_setting('simple-weather-widget-settings-group', 'weather_api_key');
    register_setting('simple-weather-widget-settings-group', 'weather_default_city');
    register_setting('simple-weather-widget-settings-group', 'weather_style_sheet');
    register_setting('simple-weather-widget-settings-group', 'weather_timezone');
}
add_action('admin_init', 'simple_weather_widget_register_settings');

// 스타일 및 스크립트 등록
function simple_weather_widget_enqueue_scripts() {
    //wp_enqueue_style('simple-weather-widget-style', plugins_url('/css/style.css', __FILE__));
    wp_enqueue_script('simple-weather-widget-script', plugins_url('/js/simple-weather-widget.js', __FILE__), array('jquery'), null, true);

    $style_sheet_option = get_option('weather_style_sheet', '1');
    switch ($style_sheet_option) {
        case '2':
            wp_enqueue_style('simple-weather-widget-style-custom', plugins_url('/css/style-custom.css', __FILE__));
            break;
        case '3':https://techplay.blog/
            wp_enqueue_style('simple-weather-widget-style-predefine', plugins_url('/css/style-predefine.css', __FILE__));
            break;
        default:
            wp_enqueue_style('simple-weather-widget-style', plugins_url('/css/style.css', __FILE__));
    }

	wp_localize_script('simple-weather-widget-script', 'weatherWidgetData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('weather_nonce'),
        'default_city' => get_option('weather_default_city', 'Seoul'),
        'api_key' => get_option('weather_api_key')
    ));
}
add_action('wp_enqueue_scripts', 'simple_weather_widget_enqueue_scripts');

// 날씨 데이터 가져오기 함수
function fetch_weather_data($city, $api_key) {
    $cache_key = 'weather_data_' . md5($city);
    $cached_data = get_transient($cache_key);

    // 캐시 만료 시간 확인
    $cache_expiration = get_option('_transient_timeout_' . $cache_key);
    if ($cached_data && $cache_expiration > time()) {
        return $cached_data;
    }

    $api_url = "https://api.openweathermap.org/data/2.5/forecast?q=" . urlencode(sanitize_text_field($city)) . "&appid=" . $api_key . "&units=metric&lang=ja"; // lang=ja 추가
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log("Weather API Error: " . $response->get_error_message());
        return new WP_Error('weather_api_error', '날씨 정보를 가져오는 중 오류가 발생했습니다.');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decoding Error: " . json_last_error_msg());
        return new WP_Error('json_decode_error', '날씨 데이터를 해석하는 중 오류가 발생했습니다.');
    }

    set_transient($cache_key, $data, 1800); // 30분 캐싱

    return $data;
}


// 날씨 아이콘 가져오기 함수
function get_weather_icon($weather_code) {
    $icon_map = array(
        '01d' => 'clear-day',
        '01n' => 'clear-night',
        '02d' => 'few-clouds',
        '02n' => 'few-clouds-night',
        '03d' => 'scattered-clouds',
        '03n' => 'scattered-clouds-night',
        '04d' => 'broken-clouds',
        '04n' => 'broken-clouds-night',
        '09d' => 'shower-rain',
        '09n' => 'shower-rain-night',
        '10d' => 'rain',
        '10n' => 'rain-night',
        '11d' => 'thunderstorm',
        '11n' => 'thunderstorm',
        '13d' => 'snow',
        '13n' => 'snow-night',
        '50d' => 'mist',
        '50n' => 'mist-night',
    );

    return isset($icon_map[$weather_code]) ? $icon_map[$weather_code] : 'default-icon';
}

// 보퍼트 풍력계급표 기준 풍속 아이콘 가져오기 함수
function get_wind_icon($wind_speed) {
    if ($wind_speed < 0.5) {
        return 'wind-beaufort-0';
    } elseif ($wind_speed < 1.6) {
        return 'wind-beaufort-1';
    } elseif ($wind_speed < 3.4) {
        return 'wind-beaufort-2';
    } elseif ($wind_speed < 5.5) {
        return 'wind-beaufort-3';
    } elseif ($wind_speed < 8.0) {
        return 'wind-beaufort-4';
    } elseif ($wind_speed < 10.8) {
        return 'wind-beaufort-5';
    } elseif ($wind_speed < 13.9) {
        return 'wind-beaufort-6';
    } elseif ($wind_speed < 17.2) {
        return 'wind-beaufort-7';
    } elseif ($wind_speed < 20.8) {
        return 'wind-beaufort-8';
    } elseif ($wind_speed < 24.5) {
        return 'wind-beaufort-9';
    } elseif ($wind_speed < 28.5) {
        return 'wind-beaufort-10';
    } elseif ($wind_speed < 32.7) {
        return 'wind-beaufort-11';
    } else {
        return 'wind-beaufort-12';
    }
}

// AJAX 요청 처리 함수
function simple_weather_widget_ajax() {
    check_ajax_referer('weather_nonce', 'nonce');

    $city = sanitize_text_field($_POST['city']);
    $api_key = get_option('weather_api_key');

    $weather_data = fetch_weather_data($city, $api_key);

    if (is_wp_error($weather_data)) {
        wp_send_json_error(array(
            'message' => $weather_data->get_error_message()
        ));
    } else {
        wp_send_json_success($weather_data);
    }
}
add_action('wp_ajax_nopriv_fetch_weather', 'simple_weather_widget_ajax');
add_action('wp_ajax_fetch_weather', 'simple_weather_widget_ajax');

function display_forecast($forecast_data) {
    $forecast_by_day = array();
    $timezone = new DateTimeZone(get_option('weather_timezone', 'UTC'));

    // 현재 시간을 사용자 설정 시간대로 가져오기
    $current_time = new DateTime('now', $timezone);
    $today = $current_time->format('Y-m-d');

    foreach ($forecast_data['list'] as $item) {
        $date = new DateTime($item['dt_txt']);
        $date->setTimezone($timezone);
        $day = $date->format('Y-m-d');
        $hour = $date->format('H');

        // 날짜를 사용자 설정 시간대로 변환하여 저장
        $day_in_timezone = $date->setTimezone($timezone)->format('Y-m-d');

        if (!isset($forecast_by_day[$day_in_timezone])) {
            $forecast_by_day[$day_in_timezone] = array(
                'temp_min' => $item['main']['temp_min'],
                'temp_max' => $item['main']['temp_max'],
                'icons' => array($item['weather'][0]['icon'] => 1),
                'has_precipitation' => false,
            );
        } else {
            $forecast_by_day[$day_in_timezone]['temp_min'] = min($forecast_by_day[$day_in_timezone]['temp_min'], $item['main']['temp_min']);
            $forecast_by_day[$day_in_timezone]['temp_max'] = max($forecast_by_day[$day_in_timezone]['temp_max'], $item['main']['temp_max']);
        }

        // 강수 여부 확인 (비 또는 눈), 하루 전체 시간대 확인
        $weather_id = $item['weather'][0]['id'];
        if (($weather_id >= 200 && $weather_id <= 300) || ($weather_id >= 500 && $weather_id <= 622)) {
            $forecast_by_day[$day_in_timezone]['has_precipitation'] = true;
        }

        // 아침 6시부터 자정까지의 데이터만 아이콘 빈도수 업데이트
        if ($hour >= 6 && $hour <= 23) {
            if (isset($forecast_by_day[$day_in_timezone]['icons'][$item['weather'][0]['icon']])) {
                $forecast_by_day[$day_in_timezone]['icons'][$item['weather'][0]['icon']]++;
            } else {
                $forecast_by_day[$day_in_timezone]['icons'][$item['weather'][0]['icon']] = 1;
            }
        }
    }

    echo '<div class="seoultour-weather-forecast">';
    $day_count = 0;
    foreach ($forecast_by_day as $day => $data) {
        if ($day == $today) continue; // 오늘 날짜는 제외
        if ($day_count >= 4) break; // 3일 예보만 표시

        // 가장 빈번한 아이콘 찾기 (아침 6시부터 자정까지)
        $most_frequent_icon = array_keys($data['icons'], max($data['icons']))[0];

        // 강수 아이콘 우선 표시 로직 (하루 전체 데이터 기준)
        $precipitation_icons = array('09d', '09n', '10d', '10n', '11d', '11n', '13d', '13n');
        $representative_icon = $data['has_precipitation'] ? '10d' : $most_frequent_icon; // 강수 시 비 아이콘, 아니면 가장 빈번한 아이콘

        echo '<div class="seoultour-forecast-day">';

        // 일본어 요일 매핑
        $weekday_map = array(
            'Sun' => '日',
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
        );
        $dateObj = new DateTime($day);
        $weekday = $weekday_map[$dateObj->format('D')];

        echo '<div class="seoultour-forecast-date">' . esc_html($weekday) . '</div>';
        echo '<div class="seoultour-forecast-icon"><img src="' . plugins_url('/icons/' . get_weather_icon($representative_icon) . '.svg', __FILE__) . '" alt="Weather Icon"></div>';
        echo '<div class="seoultour-forecast-temp-high">▲ ' . esc_html(round($data['temp_max'])) . '°C</div>';
        echo '<div class="seoultour-forecast-temp-low">▼ ' . esc_html(round($data['temp_min'])) . '°C</div>';

        // 강수 여부 표시
        $precipitation_text = $data['has_precipitation'] ? '傘 ◯' : '傘 ☓';
        echo '<div class="seoultour-forecast-precipitation">' . esc_html($precipitation_text) . '</div>';

        echo '</div>';
        $day_count++;
    }
    echo '</div>';
}

// 숏코드 함수 (5 Day / 3 Hour Forecast API 데이터 표시, 3일 예보)
function simple_weather_widget_shortcode($atts) {
    ob_start();

    $atts = shortcode_atts(array(
        'city' => get_option('weather_default_city', 'Seoul'),
        'show_temperature' => 'true',
        'show_humidity' => 'false',
        'show_sunrise' => 'false',
        'show_sunset' => 'false',
        'show_wind_speed' => 'false',
        'show_wind_direction' => 'false',
        'timezone' => get_option('weather_timezone', 'UTC'),
    ), $atts);

    $api_key = get_option('weather_api_key');

    if (empty($api_key)) {
        echo "<p>OpenWeatherMap API 키가 설정되지 않았습니다. <a href='https://openweathermap.org/home/sign_up' target='_blank'>여기</a>에서 가입하고 API 키를 설정해주세요.</p>";
        return ob_get_clean();
    }

    $weather_data = fetch_weather_data($atts['city'], $api_key);

    if (is_wp_error($weather_data)) {
        echo "<p>天気情報の取得中にエラーが発生しました: " . esc_html($weather_data->get_error_message()) . "</p>";
        return ob_get_clean();
    }

    if ($weather_data && isset($weather_data['list'])) {
    // 현재 날씨 아이콘 및 도시 이름 표시
		$weather_icon = get_weather_icon($weather_data['list'][0]['weather'][0]['icon']); 
		$icon_url = plugins_url('/icons/' . $weather_icon . '.svg', __FILE__);

		echo '<div id="weather-widget">';
		echo '<div class="techplay-weather-icon weather"><img src="' . esc_url($icon_url) . '" alt="Weather Icon"></div>';
		echo '<div class="techplay-weather-city"><strong>' . esc_html($weather_data['city']['name']) . '</strong> - ';
        $timezone = new DateTimeZone($atts['timezone']);
        $today = new DateTime('now', $timezone); 
        
        // 일본어 요일 매핑
        $weekday_map = array(
            'Sun' => '日',
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
        );

        $weekday = $weekday_map[$today->format('D')]; // 영어 요일을 일본어로 매핑

        echo esc_html($today->format('n月j日') . '(' . $weekday . ')') . '</div>'; 
		
		// 현재 기후 상태 텍스트 표시
	    echo '<div class="techplay-weather-description">' . esc_html($weather_data['list'][0]['weather'][0]['description']) . '</div>'; 

        // 오늘 최고/최저 온도 표시 (수정)
        echo '<div class="techplay-weather-temp-range">';
        $timezone = new DateTimeZone($atts['timezone']);
        $today = new DateTime('now', $timezone); // 현재 시간을 사용자 설정 시간대로 가져오기
        $today_date_str = $today->format('Y-m-d'); 
        $temp_min = PHP_INT_MAX; 
        $temp_max = -PHP_INT_MAX; 
        foreach ($weather_data['list'] as $item) {
            $item_date = date('Y-m-d', strtotime($item['dt_txt']));
            if ($item_date == $today_date_str) {
                $temp_min = min($temp_min, $item['main']['temp_min']);
                $temp_max = max($temp_max, $item['main']['temp_max']);
            }
        }

        if ($temp_min !== PHP_INT_MAX && $temp_max !== -PHP_INT_MAX) {
            echo '<span class="temp-high">最高 ' . esc_html(round($temp_max)) . '°C</span> / ';
            echo '<span class="temp-low">最低 ' . esc_html(round($temp_min)) . '°C</span>';
        } else {
            echo '<span class="temp-unavailable">最高/最低気温情報なし</span>';
        }
        echo '</div>';

		// 현재 기온 표시
        if ('true' === $atts['show_temperature']) {
            $temperature = round($weather_data['list'][0]['main']['temp'], 1);
            echo '<div class="techplay-weather-icon temp"><img src="' . plugins_url('/icons/thermometer.svg', __FILE__) . '" alt="Temperature Icon"> ' . esc_html($temperature) . '°C ';
        }

		// 현재 습도 표시
        if ('true' === $atts['show_humidity']) {
            echo '<img src="' . plugins_url('/icons/humidity.svg', __FILE__) . '" alt="Humidity Icon"> ' . esc_html($weather_data['list'][0]['main']['humidity']) . '% ';
        }

        $timezone = new DateTimeZone($atts['timezone']);

        // 오늘 강수 여부 확인
        $today = date('Y-m-d');
        $has_precipitation_today = false;
        foreach ($weather_data['list'] as $item) {
            $item_date = date('Y-m-d', strtotime($item['dt_txt']));
            if ($item_date == $today) {
                $weather_id = $item['weather'][0]['id'];
                if (($weather_id >= 200 && $weather_id <= 300) || ($weather_id >= 500 && $weather_id <= 622)) {
                    $has_precipitation_today = true;
                    break; 
                }
            }
        }

        // 우산 지참 필요 여부 표시
        $umbrella_text = $has_precipitation_today ? '必要' : '不要';
        echo '<img src="' . plugins_url('/icons/umbrella.svg', __FILE__) . '" alt="Umbrella Icon" height="20px"> ' . esc_html($umbrella_text) . '</div>';

        // 현재 일출/일몰 시간 표시
        if ('true' === $atts['show_sunrise'] || 'true' === $atts['show_sunset']) {
            $today = date('Y-m-d');
            $sunrise = new DateTime('@' . $weather_data['city']['sunrise']);
            $sunrise->setTimezone($timezone);
            $sunset = new DateTime('@' . $weather_data['city']['sunset']);
            $sunset->setTimezone($timezone);

            if ('true' === $atts['show_sunrise']) {
                echo '<div class="techplay-weather-icon sunrise"><img src="' . plugins_url('/icons/sunrise.svg', __FILE__) . '" alt="Sunrise Icon"> 日の出 ' . esc_html($sunrise->format('H:i')) . ', ';
            }

            if ('true' === $atts['show_sunset']) {
                echo '日の入り ' . esc_html($sunset->format('H:i')) . '</div>';
            }
        }
/*
        // 현재 풍속 및 풍향 표시
        if ('true' === $atts['show_wind_speed'] || 'true' === $atts['show_wind_direction']) {
            $wind_speed = $weather_data['list'][0]['wind']['speed'];
            $wind_icon = get_wind_icon($wind_speed);
            $wind_icon_url = plugins_url('/icons/' . $wind_icon . '.svg', __FILE__);

            if ('true' === $atts['show_wind_speed']) {
                echo '<div class="techplay-weather-icon wind"><img src="' . esc_url($wind_icon_url) . '" alt="Wind Icon"> ' . esc_html($wind_speed) . ' m/s</div>';
            }

            if ('true' === $atts['show_wind_direction']) {
                echo '<div class="techplay-weather-icon wind-direction"><img src="' . plugins_url('/icons/windsock.svg', __FILE__) . '" alt="Wind Direction Icon"> ' . esc_html($weather_data['list'][0]['wind']['deg']) . ' degrees</div>';
            }
        }
*/
		// 3일간 날씨 예보 표시
        display_forecast($weather_data); 

        echo '</div>'; 
    } else {
        echo '<div id="weather-widget"><p>天気情報が現在利用できません。</p></div>';
    }

    return ob_get_clean();
}

add_shortcode('simple_weather_widget', 'simple_weather_widget_shortcode');
?>
document.addEventListener('DOMContentLoaded', function() {
    // 날씨 데이터를 AJAX를 통해 가져오는 함수
    function fetchWeather(city, containerId) {
        jQuery.post(weatherWidgetData.ajax_url, {
            'action': 'fetch_weather',
            'city': city,
            '_ajax_nonce': weatherWidgetData.nonce
        }, function(response) {
            if (response.success) {
                updateWeatherDisplay(response.data, containerId);
            } else {
                console.error('Error fetching weather data:', response);
            }
        });
    }

    // 날씨 데이터를 페이지에 표시하는 함수
    function updateWeatherDisplay(weatherData, containerId) {
        var weatherIcon = getWeatherIcon(weatherData.weather[0].icon);
        var iconUrl = pluginsUrl + '/icons/' + weatherIcon + '.svg'; // SVG 파일 경로

        var weatherWidget = document.getElementById(containerId);
        if (weatherWidget) {
            weatherWidget.innerHTML = `
                <div class="techplay-weather-icon weather"><img src="${iconUrl}" alt="Weather Icon"></div>
                <div class="techplay-weather-city"><strong>${weatherData.name}</strong></div>
                <div class="techplay-weather-temp">${weatherData.main.temp.toFixed(1)}°C</div>
                <div class="techplay-weather-humidity">${weatherData.main.humidity}%</div>
                <div class="techplay-weather-sunrise">${new Date(weatherData.sys.sunrise * 1000).toLocaleTimeString()}</div>
                <div class="techplay-weather-sunset">${new Date(weatherData.sys.sunset * 1000).toLocaleTimeString()}</div>
                <div class="techplay-weather-wind">${weatherData.wind.speed} m/s</div>
                <div class="techplay-weather-wind-direction">${weatherData.wind.deg}°</div>
                <!-- 여기에 추가 날씨 정보 표시 -->
            `;
        }
    }

    // 날씨 아이콘을 가져오는 함수
    function getWeatherIcon(iconCode) {
        var iconMap = {
            '01d': 'clear-day',
            '01n': 'clear-night',
            '02d': 'few-clouds',
            '02n': 'few-clouds-night',
            '03d': 'scattered-clouds',
            '03n': 'scattered-clouds-night',
            '04d': 'broken-clouds',
            '04n': 'broken-clouds-night',
            '09d': 'shower-rain',
            '09n': 'shower-rain-night',
            '10d': 'rain',
            '10n': 'rain-night',
            '11d': 'thunderstorm',
            '11n': 'thunderstorm-night',
            '13d': 'snow',
            '13n': 'snow-night',
            '50d': 'mist',
            '50n': 'mist-night',
        };

        return iconMap[iconCode] || 'default-icon';
    }

    // 페이지에 있는 모든 날씨 위젯에 대해 데이터 요청
    var weatherWidgets = document.querySelectorAll('.simple-weather-widget');
    weatherWidgets.forEach(function(widget, index) {
        var city = widget.dataset.city || weatherWidgetData.default_city;
        fetchWeather(city, 'weather-widget-' + index);
    });
});

# 워드프레스 테크플레이 심플 날씨 위젯

**Contributors:** NoxWon  
**Donate link:** [techplay.blog](https://techplay.blog)  
**Tags:** weather, widget, openweathermap, shortcode  
**Requires at least:** 4.6  
**Tested up to:** 5.7  
**Stable tag:** 1.22  
**License:** GPLv2 or later  
**License URI:** [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

워드프레스를 위한 간단하고 사용하기 쉬운 날씨 위젯 플러그인입니다. OpenWeatherMap API를 사용하여 현재 날씨 정보를 제공합니다.

## Description

"워드프레스 테크플레이 심플 날씨 위젯"은 사용자가 쉽게 현재 날씨 정보를 워드프레스 사이트에 표시할 수 있게 해주는 플러그인입니다. OpenWeatherMap API를 활용하여 정확한 날씨 데이터를 제공하며, 간단한 설정과 함께 여러 옵션을 통해 날씨 정보를 사용자 정의할 수 있습니다.

**주요 특징:**
- 관리자 메뉴를 통한 날씨를 보여줄 도시 지정 기능
- OpenWeatherMap API 키 설정을 통한 데이터 연동
- 숏코드를 통한 웹사이트 어디에나 쉽게 위젯 배치
- 온도, 습도, 풍속, 풍향, 일출 및 일몰 정보 표시(선택적)
- SVG 아이콘을 사용한 동적 날씨 아이콘 적용
- 타임존 별 정확한 일출 및 일몰 시간 표시

**데모 확인**
[워드프레스 테크플레이 심플 날씨 위젯 플러그인 데모 보기](https://techplay.blog/%ec%9b%8c%eb%93%9c%ed%94%84%eb%a0%88%ec%8a%a4-%ed%85%8c%ed%81%ac%ed%94%8c%eb%a0%88%ec%9d%b4-%ec%8b%ac%ed%94%8c-%eb%82%a0%ec%94%a8-%ec%9c%84%ec%a0%af/)

## Installation

1. 'wp-content/plugins/' 디렉토리에 플러그인을 업로드합니다.
2. 워드프레스 관리자 페이지에서 '플러그인' 메뉴를 통해 플러그인을 활성화합니다.
3. '설정 > Weather Widget'에서 API 키와 기본 도시 및 타임존을 설정합니다.
4. 포스트나 페이지에 숏코드를 사용하여 날씨 위젯을 표시합니다.

## Frequently Asked Questions

**Q: OpenWeatherMap API 키는 어디에서 얻을 수 있나요?**  
A: OpenWeatherMap 웹사이트([openweathermap.org](https://openweathermap.org))에서 무료로 가입하여 API 키를 얻을 수 있습니다.

## Shortcodes

- `[simple_weather_widget]`: 기본 설정으로 날씨 정보 표시
- `[simple_weather_widget show_temperature="true"]`: 온도 표시
- `[simple_weather_widget show_wind_speed="true" show_wind_direction="true"]`: 풍속 및 풍향 표시
- `[simple_weather_widget city="Seoul" timezone="Asia/Seoul"]`: 서울의 날씨와 일출, 일몰 시간을 서울 타임존에 맞춰 표시

## Changelog

### 1.22
- 새로운 숏코드 옵션 및 AJAX 업데이트 기능 추가
- 타임존 설정과 관련된 기능이 포함되어 있습니다. 업데이트 후 타임존 설정을 확인하세요.
- 사용자 정의 스타일 추가 - 관리자 화면에서 다른 스타일을 지정할 수 있습니다. 
(현재 style.css외 별도 스타일 파일은 아무것도 정의 되어 있지 않습니다.)

### 1.21
- 성능 문제로 30분 단위로 캐싱 
- 날씨 정보 갱신 시 AJAX 형태로 페이지 새로 고침 없이 바로 적용 
- 관리자 화면에서 타임존 설정 


## Upgrade Notice

### 1.22
- 새로운 숏코드 옵션 및 AJAX 업데이트 기능 추가
- 타임존 설정과 관련된 기능이 포함되어 있습니다. 업데이트 후 타임존 설정을 확인하세요.

## Author

- **이름:** NoxWon
- **이메일:** [wonhongsik@gmail.com](mailto:wonhongsik@gmail.com)
- **웹사이트:** [techplay.blog](https://techplay.blog)

## Credits

이 프로젝트에 사용된 날씨 아이콘은 [Meteocons](https://bas.dev/work/meteocons)에서 제공되었습니다. Meteocons는 다양한 날씨 상황을 나타내는 무료 SVG 아이콘 세트입니다.

## License

이 플러그인은 GPLv2 라이센스 하에 무료로 배포됩니다. 자세한 내용은 [여기](https://www.gnu.org/licenses/gpl-2.0.html)에서 확인하실 수 있습니다.
=======
# WP-TechPlayWeatherWidget

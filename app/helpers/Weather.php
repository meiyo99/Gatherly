<?php

class Weather {
    private $apiKey;
    private $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct() {
        // Get API key from config
        $this->apiKey = WEATHER_API_KEY;
    }

    /**
     * Get weather for a specific location and date
     * If date is more than 5 days away, falls back to current weather
     */
    public function getWeatherForEvent($location, $eventDate) {
        if (empty($this->apiKey)) {
            return null; // API key not configured
        }

        // Calculate days until event
        $now = new DateTime();
        $eventDateTime = new DateTime($eventDate);
        $daysUntil = $now->diff($eventDateTime)->days;
        $isPast = $eventDateTime < $now;

        // Get coordinates for location first
        $coords = $this->getCoordinates($location);
        if (!$coords) {
            return null; // couldn't geocode location
        }

        // If event is in the past or more than 5 days away, get current weather
        // OpenWeather free tier only supports 5-day forecast
        if ($isPast || $daysUntil > 5) {
            return $this->getCurrentWeather($coords['lat'], $coords['lon']);
        } else {
            // Try to get forecast for the specific date
            $forecast = $this->getForecast($coords['lat'], $coords['lon'], $eventDateTime);
            if ($forecast) {
                return $forecast;
            }
            // Fallback to current weather if forecast fails
            return $this->getCurrentWeather($coords['lat'], $coords['lon']);
        }
    }

    /**
     * Convert location string to coordinates using geocoding API
     */
    private function getCoordinates($location) {
        $url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($location) . "&limit=1&appid={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (empty($data)) {
            return null;
        }

        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon']
        ];
    }

    /**
     * Get current weather for coordinates
     */
    private function getCurrentWeather($lat, $lon) {
        $url = "{$this->baseUrl}/weather?lat={$lat}&lon={$lon}&units=imperial&appid={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['main'])) {
            return null;
        }

        // Format the response
        return [
            'temp' => round($data['main']['temp']),
            'feels_like' => round($data['main']['feels_like']),
            'temp_min' => round($data['main']['temp_min']),
            'temp_max' => round($data['main']['temp_max']),
            'humidity' => $data['main']['humidity'],
            'description' => ucfirst($data['weather'][0]['description']),
            'icon' => $data['weather'][0]['icon'],
            'wind_speed' => round($data['wind']['speed']),
            'is_forecast' => false // this is current weather
        ];
    }

    /**
     * Get forecast for specific date
     */
    private function getForecast($lat, $lon, $targetDate) {
        $url = "{$this->baseUrl}/forecast?lat={$lat}&lon={$lon}&units=imperial&appid={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['list'])) {
            return null;
        }

        // Find the forecast closest to the target date
        $targetTimestamp = $targetDate->getTimestamp();
        $closestForecast = null;
        $smallestDiff = PHP_INT_MAX;

        foreach ($data['list'] as $forecast) {
            $forecastTime = $forecast['dt'];
            $diff = abs($targetTimestamp - $forecastTime);

            if ($diff < $smallestDiff) {
                $smallestDiff = $diff;
                $closestForecast = $forecast;
            }
        }

        if (!$closestForecast) {
            return null;
        }

        // Format the response similar to current weather
        return [
            'temp' => round($closestForecast['main']['temp']),
            'feels_like' => round($closestForecast['main']['feels_like']),
            'temp_min' => round($closestForecast['main']['temp_min']),
            'temp_max' => round($closestForecast['main']['temp_max']),
            'humidity' => $closestForecast['main']['humidity'],
            'description' => ucfirst($closestForecast['weather'][0]['description']),
            'icon' => $closestForecast['weather'][0]['icon'],
            'wind_speed' => round($closestForecast['wind']['speed']),
            'is_forecast' => true // this is a forecast
        ];
    }

    /**
     * Get Bootstrap icon class based on OpenWeather icon code
     */
    public static function getIconClass($iconCode) {
        // Map OpenWeather icon codes to Bootstrap icons
        $iconMap = [
            '01d' => 'bi-sun-fill',
            '01n' => 'bi-moon-fill',
            '02d' => 'bi-cloud-sun-fill',
            '02n' => 'bi-cloud-moon-fill',
            '03d' => 'bi-cloud-fill',
            '03n' => 'bi-cloud-fill',
            '04d' => 'bi-cloudy-fill',
            '04n' => 'bi-cloudy-fill',
            '09d' => 'bi-cloud-drizzle-fill',
            '09n' => 'bi-cloud-drizzle-fill',
            '10d' => 'bi-cloud-rain-fill',
            '10n' => 'bi-cloud-rain-fill',
            '11d' => 'bi-cloud-lightning-fill',
            '11n' => 'bi-cloud-lightning-fill',
            '13d' => 'bi-cloud-snow-fill',
            '13n' => 'bi-cloud-snow-fill',
            '50d' => 'bi-cloud-haze-fill',
            '50n' => 'bi-cloud-haze-fill',
        ];

        return $iconMap[$iconCode] ?? 'bi-cloud-fill';
    }
}

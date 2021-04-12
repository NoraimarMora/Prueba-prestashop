<div class="weather-container">
    <div id="orig" class="row">
    <div class="weather-img-container col-md-2">
        <img src="{$weather['condition_icon']}" >
    </div>
    <div class="weather-info-container col-md-10">
        <span class="weather-info city">{$weather['city']}, {$weather['country']}. </span>
        <span class="weather-info">{l s='Condition'}: {$weather['condition']}. </span>
        <span class="weather-info">{l s='Temperature' mod='weatherinfo'}: {$weather['temp_c']}°C. </span>
        <span class="weather-info">{l s='Feels like' mod='weatherinfo'}: {$weather['feelslike_c']}°C. </span>
        <span class="weather-info">{l s='Humidity' mod='weatherinfo'}: {$weather['humidity']}%. </span>
        <span class="weather-info">{l s='Wind' mod='weatherinfo'}: {$weather['wind_kph']}km/h. </span>
        <span class="weather-info">{l s='Last updated' mod='weatherinfo'}: {$weather['last_updated']}</span>
    </div>
    </div>
</div>
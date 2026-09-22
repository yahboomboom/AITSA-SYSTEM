import { useEffect, useState } from 'react';

// AITSA's campus in Cabuyao, Laguna — fixed since the dashboard only ever
// needs weather for this one location, not a user-chosen city.
const LATITUDE = 14.2786;
const LONGITUDE = 121.1189;
const FORECAST_URL = `https://api.open-meteo.com/v1/forecast?latitude=${LATITUDE}&longitude=${LONGITUDE}&current_weather=true&timezone=Asia%2FManila`;

// WMO weather codes, grouped down to what's actually useful to show a student.
const WEATHER_CODES = {
    0: { label: 'Clear sky', icon: 'fa-sun' },
    1: { label: 'Mainly clear', icon: 'fa-cloud-sun' },
    2: { label: 'Partly cloudy', icon: 'fa-cloud-sun' },
    3: { label: 'Overcast', icon: 'fa-cloud' },
    45: { label: 'Foggy', icon: 'fa-smog' },
    48: { label: 'Foggy', icon: 'fa-smog' },
    51: { label: 'Light drizzle', icon: 'fa-cloud-rain' },
    53: { label: 'Drizzle', icon: 'fa-cloud-rain' },
    55: { label: 'Heavy drizzle', icon: 'fa-cloud-rain' },
    61: { label: 'Light rain', icon: 'fa-cloud-showers-heavy' },
    63: { label: 'Rain', icon: 'fa-cloud-showers-heavy' },
    65: { label: 'Heavy rain', icon: 'fa-cloud-showers-heavy' },
    80: { label: 'Rain showers', icon: 'fa-cloud-showers-heavy' },
    81: { label: 'Rain showers', icon: 'fa-cloud-showers-heavy' },
    82: { label: 'Violent showers', icon: 'fa-cloud-showers-heavy' },
    95: { label: 'Thunderstorm', icon: 'fa-cloud-bolt' },
    96: { label: 'Thunderstorm w/ hail', icon: 'fa-cloud-bolt' },
    99: { label: 'Thunderstorm w/ hail', icon: 'fa-cloud-bolt' },
};

function describeWeatherCode(code) {
    return WEATHER_CODES[code] ?? { label: 'Weather', icon: 'fa-cloud' };
}

export default function WeatherCard() {
    const [weather, setWeather] = useState(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let cancelled = false;

        fetch(FORECAST_URL)
            .then((res) => {
                if (!res.ok) throw new Error('Weather request failed');
                return res.json();
            })
            .then((data) => {
                if (!cancelled && data?.current_weather) setWeather(data.current_weather);
            })
            .catch(() => {
                if (!cancelled) setFailed(true);
            });

        return () => { cancelled = true; };
    }, []);

    const condition = weather ? describeWeatherCode(weather.weathercode) : null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-3">
                <i className="fa-solid fa-cloud-sun text-brandGreen mr-2" />Cabuyao Weather
            </h2>
            {failed && (
                <p className="text-sm text-brandNavy/40 dark:text-slate-500">Weather unavailable right now.</p>
            )}
            {!failed && !weather && (
                <p className="text-sm text-brandNavy/40 dark:text-slate-500">Loading…</p>
            )}
            {weather && condition && (
                <div className="flex items-center gap-3">
                    <i className={`fa-solid ${condition.icon} text-3xl text-brandGold`} />
                    <div>
                        <p className="text-2xl font-heading font-semibold text-brandNavy dark:text-white">{Math.round(weather.temperature)}&deg;C</p>
                        <p className="text-sm text-brandNavy/50 dark:text-slate-400">{condition.label}</p>
                    </div>
                </div>
            )}
        </div>
    );
}

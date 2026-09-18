export const DEFAULT_PHONE_REGION = 'ID';

export type PhoneCountry = {
    region: string;
    callingCode: string;
    name: string;
};

export const PHONE_COUNTRIES: PhoneCountry[] = [
    { region: 'ID', callingCode: '62', name: 'Indonesia' },
    { region: 'SG', callingCode: '65', name: 'Singapore' },
    { region: 'MY', callingCode: '60', name: 'Malaysia' },
    { region: 'AU', callingCode: '61', name: 'Australia' },
    { region: 'JP', callingCode: '81', name: 'Japan' },
    { region: 'KR', callingCode: '82', name: 'South Korea' },
    { region: 'CN', callingCode: '86', name: 'China' },
    { region: 'TW', callingCode: '886', name: 'Taiwan' },
    { region: 'HK', callingCode: '852', name: 'Hong Kong' },
    { region: 'TH', callingCode: '66', name: 'Thailand' },
    { region: 'VN', callingCode: '84', name: 'Vietnam' },
    { region: 'PH', callingCode: '63', name: 'Philippines' },
    { region: 'IN', callingCode: '91', name: 'India' },
    { region: 'AE', callingCode: '971', name: 'United Arab Emirates' },
    { region: 'SA', callingCode: '966', name: 'Saudi Arabia' },
    { region: 'US', callingCode: '1', name: 'United States' },
    { region: 'GB', callingCode: '44', name: 'United Kingdom' },
    { region: 'DE', callingCode: '49', name: 'Germany' },
    { region: 'FR', callingCode: '33', name: 'France' },
    { region: 'NL', callingCode: '31', name: 'Netherlands' },
    { region: 'NZ', callingCode: '64', name: 'New Zealand' },
];

export function phoneCountryByRegion(region?: string | null): PhoneCountry {
    const code = (region ?? DEFAULT_PHONE_REGION).toUpperCase();

    return (
        PHONE_COUNTRIES.find((country) => country.region === code) ??
        PHONE_COUNTRIES[0]
    );
}

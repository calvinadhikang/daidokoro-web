import { inputClassName } from '@/components/admin/menu-form';
import {
    DEFAULT_PHONE_REGION,
    PHONE_COUNTRIES,
    phoneCountryByRegion,
} from '@/lib/phone-countries';
import { cn } from '@/lib/utils';

type PhoneInputProps = {
    id?: string;
    country: string;
    nationalNumber: string;
    onCountryChange: (region: string) => void;
    onNationalNumberChange: (value: string) => void;
    disabled?: boolean;
    autoComplete?: string;
};

export function PhoneInput({
    id = 'phone',
    country,
    nationalNumber,
    onCountryChange,
    onNationalNumberChange,
    disabled = false,
    autoComplete = 'tel',
}: PhoneInputProps) {
    const selected = phoneCountryByRegion(country || DEFAULT_PHONE_REGION);

    return (
        <div className="flex min-w-0 items-stretch gap-2">
            <label htmlFor={`${id}-country`} className="sr-only">
                Country code
            </label>
            <select
                id={`${id}-country`}
                value={selected.region}
                disabled={disabled}
                onChange={(event) => onCountryChange(event.target.value)}
                className={cn(
                    inputClassName,
                    'w-29 shrink-0 grow-0 basis-29 pr-7',
                )}
            >
                {PHONE_COUNTRIES.map((item) => (
                    <option key={item.region} value={item.region}>
                        +{item.callingCode} {item.region}
                    </option>
                ))}
            </select>
            <input
                id={id}
                type="tel"
                inputMode="tel"
                value={nationalNumber}
                disabled={disabled}
                autoComplete={autoComplete}
                placeholder={
                    selected.region === DEFAULT_PHONE_REGION
                        ? '0812...'
                        : 'National number'
                }
                onChange={(event) =>
                    onNationalNumberChange(
                        event.target.value.replace(/[^\d+]/g, ''),
                    )
                }
                className={cn(inputClassName, 'min-w-0 w-0 flex-1')}
            />
        </div>
    );
}

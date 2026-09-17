type CustomerSearchFields = {
    name: string;
    phone: string;
    phone_display?: string | null;
    phone_local?: string | null;
};

export function localPhoneDigits(value: string): string {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('0')) {
        digits = digits.slice(1);
    }

    if (digits.startsWith('62') && digits.length > 2) {
        digits = digits.slice(2);
    }

    return digits;
}

export function customerMatchesSearch(
    customer: CustomerSearchFields,
    rawQuery: string,
): boolean {
    const query = rawQuery.trim().toLowerCase();
    if (query === '') {
        return true;
    }

    if (customer.name.toLowerCase().includes(query)) {
        return true;
    }

    const queryDigits = localPhoneDigits(query);
    if (queryDigits === '') {
        return false;
    }

    return [customer.phone, customer.phone_display, customer.phone_local]
        .filter((value): value is string => Boolean(value))
        .some((phone) => localPhoneDigits(phone).includes(queryDigits));
}

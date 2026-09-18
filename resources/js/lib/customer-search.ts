type CustomerSearchFields = {
    name: string;
    phone: string;
    phone_display?: string | null;
    phone_local?: string | null;
};

function digitsOnly(value: string): string {
    return value.replace(/\D/g, '');
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

    const queryDigits = digitsOnly(query);
    if (queryDigits === '') {
        return false;
    }

    const candidates = [
        customer.phone,
        customer.phone_display,
        customer.phone_local,
    ]
        .filter((value): value is string => Boolean(value))
        .map(digitsOnly);

    if (candidates.some((phone) => phone.includes(queryDigits))) {
        return true;
    }

    if (queryDigits.startsWith('0') && queryDigits.length > 1) {
        const withoutTrunk = queryDigits.slice(1);

        return candidates.some((phone) => phone.includes(withoutTrunk));
    }

    return false;
}

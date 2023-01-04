export function loopOverRegistrations(registrations, cb) {
    return registrations.map((registration) => cb(registration));
}


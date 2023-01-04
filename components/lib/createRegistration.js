export function createRegistration(sideevent, role) {
    return {
        role: role,
        sideevent: sideevent,
        event: evfranking.eventid
    };
}

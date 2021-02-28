export function pad(number) {
    if (number < 10) {
        return '0' + number;
    }
    return number;
}
export function format_date(dt) {
    return dt.getFullYear() +
        '-' + pad(dt.getMonth() + 1) +
        '-' + pad(dt.getDate());
};

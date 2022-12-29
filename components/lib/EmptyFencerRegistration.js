export function emptyFencerRegistration(lastname, country) {
    return {
        id: -1,
        name: lastname,
        fullname: '',
        firstname: '',
        birthday: '',
        category: null,
        category_num: 1,
        birthyear: null,
        country: country.id,
        country_name: country.name,
        gender: 'M'
    };
}
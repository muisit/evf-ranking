import { is_organisation, is_sysop, is_valid } from '../functions';

export function defaultPayment(country, fencer) {
    var paysIndividual = 'G';
    if (is_organisation() && (!country || !is_valid(country.id))) paysIndividual = 'O';
    if (is_sysop() && (!country || !is_valid(country.id))) paysIndividual='E';
    if(fencer && fencer.registrations) {
        fencer.registrations.map((itm) => {
            if(itm.payment == 'I') {                
                paysIndividual = 'I';
            }
        });
    }
    return paysIndividual;    
}
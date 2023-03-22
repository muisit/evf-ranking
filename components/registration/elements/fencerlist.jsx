import { is_valid } from '../../functions';

export function FencerList(props) {
    if (!props.fencers || !props.basic) {
        return (<div></div>);
    }

    var fencers = props.fencers.slice();
    // sort based on and name
    fencers.sort(function (a1, a2) {
            return a1.fullname > a2.fullname;
    });

    return (
        <table className='style-stripes'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>First name</th>
                    {!is_valid(props.country) && (<th>Country</th>)}
                    <th>Gender</th>
                    <th>YOB</th>
                    <th>Category</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {fencers.map((fencer,idx) => (
                        <tr key={idx} onClick={(e) => props.onSelect(fencer)}>
                            <td>{fencer.name}</td>
                            {is_valid(fencer.id) && (<td>{fencer.firstname}</td>)}
                            {!is_valid(props.country) && is_valid(fencer.id) && (<td>{fencer.country_name}</td>)}
                            {is_valid(fencer.id) && (<td>{fencer.fullgender}</td>)}
                            {is_valid(fencer.id) && (<td>{fencer.birthyear}</td>)}
                            {is_valid(fencer.id) && (<td>{fencer.category}</td>)}
                            {!is_valid(fencer.id) && (<td colSpan='4' style={{textAlign:'center'}}>&lt; add a new entry &gt;</td>)}
                            <td><i className='pi pi-chevron-circle-right'></i></td>
                        </tr>
                    ))}
            </tbody>
        </table>
    );
}
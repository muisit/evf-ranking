
export function FencerList(props) {
    if (!props.fencers || !props.basic) {
        return (<div></div>);
    }

    var fencers = props.fencers.slice();
    // sort based on and name
    fencers.sort(function (a1, a2) {
            return a1.fullname > a2.fullname;
    });
    console.log(fencers);

    return (
        <table className='style-stripes'>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>First name</th>
                    <th>Country</th>
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
                            <td>{fencer.firstname}</td>
                            <td>{fencer.country_name}</td>
                            <td>{fencer.fullgender}</td>
                            <td>{fencer.birthyear}</td>
                            <td>{fencer.category}</td>
                            <td><a onClick={(e) => props.onSelect(fencer)}><i className='pi pi-chevron-circle-right'></i></a></td>
                        </tr>
                    ))}
            </tbody>
        </table>
    );
}
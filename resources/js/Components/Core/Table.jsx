import {Link} from "@inertiajs/react";

const Table = ({fields, items, title, pagination}) => {

    return (

        <div className="card">
            <div className="card-header">
                <h3 className="card-title">{title}</h3>
            </div>

            <div className="card-body">
                <table className="table table-bordered">
                    <thead>
                    <tr>
                        {fields.map((field, index) => (<th key={index}>{field}</th>))}
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    {items.map((item, index) => (<tr>
                        {Object.keys(item).map((key, index) => (<td key={index}>{item[key]}</td>))}
                        <td style={{width: '150px'}} className={'text-center'}>
                            <Link
                                href="route('admin.categories.edit', category.id)"
                                className="btn btn-success btn-sm mx-2">
                                <i className="fa fa-edit"></i>
                            </Link>
                            <Link
                                href="route('admin.categories.edit', category.id)"
                                className="btn btn-danger btn-sm">
                                <i className="fa fa-trash"></i>
                            </Link>
                        </td>
                    </tr>))}
                    </tbody>
                </table>
            </div>

            <div className="card-footer clearfix">
                <ul className="pagination pagination-sm m-0 float-right">
                    <li className="page-item"><a className="page-link" href="#">«</a></li>
                    <li className="page-item"><a className="page-link" href="#">1</a></li>
                    <li className="page-item"><a className="page-link" href="#">2</a></li>
                    <li className="page-item"><a className="page-link" href="#">3</a></li>
                    <li className="page-item"><a className="page-link" href="#">»</a></li>
                </ul>
            </div>
        </div>)
}


export default Table

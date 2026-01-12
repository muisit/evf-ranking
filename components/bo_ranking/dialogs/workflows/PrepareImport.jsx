import React from 'react';
import { Button } from 'primereact/button';
import { workflow, error_handler } from "../../../api.js";

export default class PrepareImport extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }

    finish = () => {
        this.loading(true);
        workflow('step', {id: this.props.value.id, step: 'done'})
            .then((r) => {
                this.loading(false);
                this.onClose();        
            });
    }

    unpack = () => {
        var file_id = this.props.value.sandbox.file_id ?? -1;
        for (var i = 0;i < this.props.value.sandbox.files.length; i++) {
            if (this.props.value.sandbox.files[i].unpacked) {
                if (this.props.value.sandbox.files[i].id == file_id) {
                    // pick the next
                    file_id = -1;
                }
                else if(file_id == -1) {
                    file_id = this.props.value.sandbox.files[i].id;
                    break;
                }
            }
        }

        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'import_file',
            file_id: file_id
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    }

    render() {
        const allProcessed = this.props.value.sandbox.files.filter((i) => i.processed);
        const allUnpacked  = this.props.value.sandbox.files.filter((i) => i.unpacked);
        var button = (<Button label="Continue" icon="pi pi-caret-right" className="p-button-primary p-button-raised p-button-text" onClick={this.unpack} />);
        if (allProcessed.length == allUnpacked.length) {
            button = (<Button label="Finish" icon="pi pi-check" className="p-button-primary p-button-raised p-button-text" onClick={this.finish} />);
        }

        return (
      <div>
        <div className='title'>Processing Files</div>
        <table className='files'>
            <tbody>
        {this.props.value.sandbox?.files && this.props.value.sandbox.files.map((item) => {
            if (item && item.unpacked) {
                return (<tr key={item.id} className='file'>
                    <td className='filename'>{item.name}</td>
                    <td className='compname'>{item.competition}</td>
                    <td>
                        {item.processed && (<i className="pi pi-check"></i>)}
                        {!item.processed && (<i className="pi pi-spinner"></i>)}
                    </td>
                </tr>);
            }
        })
        }
          </tbody>
        </table>
        <div className='alignright'>
            {button}
        </div>
      </div>
        );
    }
}


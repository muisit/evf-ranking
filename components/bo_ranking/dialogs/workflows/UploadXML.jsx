import React from 'react';
import { workflow, error_handler, upload_file } from "../../../api.js";
import { FileUpload } from 'primereact/fileupload';

export default class UploadXML extends React.Component {
    constructor(props, context) {
        super(props, context);
        this.state = {
            file_id:-1
        }
    }

    loading = (state) => {
        if(this.props.onLoad) this.props.onLoad(state);
    }

    onCancel = () => {
        if (this.props.onCancel) this.props.onCancel();
        this.close();
    }

    onClose = () => {
        if (this.props.onClose) this.props.onClose();
    }    

    onUpload = () => {
        this.loading(true);
        workflow('step', {
            id: this.props.value.id,
            step: 'uploaded',
            file_id: this.state.file_id
        })
        .then((json) => {
            this.loading(false);
            if (this.props.onFinish) this.props.onFinish(json.data);
        })
        .catch(error_handler);
    }

    doUpload = (f) => {
        this.loading(true);
        upload_file('/workflow/upload', 'events', f.files[0], {id: this.props.value.id})
            .then((data) => {
                this.loading(false);
                if (data.data.model) {
                    this.setState({file_id:data.data.model.file_id}, () => this.onUpload());
                }
            })
            .catch(error_handler);
    }

    render() {
        return (
      <div>
        <label>File</label>
        <div className='input'>
            <FileUpload name='file' customUpload uploadHandler={this.doUpload} accept="*/*" maxFileSize={1000000} auto/>
        </div>
      </div>
        );
    }
}


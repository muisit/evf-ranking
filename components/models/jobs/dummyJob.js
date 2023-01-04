export function createDummyJob (callback) {
    return {
        callback: callback,

        execute: function() {
            return new Promise((r,e) => { return r(this); })
                .then((job) => {
                    this.callback();
                    return job;
                });
        }
    }
}
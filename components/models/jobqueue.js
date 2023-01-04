import { random_token } from "../functions";

export function useJobQueue () {
    return {
        jobs: {},
        lastIndex: 0,
        isRunning: false,
        
        add: function (job) {
            job.hash = random_token(15);
            job.index = this.lastIndex;
            this.lastIndex += 1;
            this.jobs[job.hash] = job;
        },

        remove: function (job) {
            if (this.jobs[job.hash]) {
                var newjobs = {};
                Object.keys(this.jobs).map((key) => {
                    var job2 = this.jobs[key];
                    if (job2.hash != job.hash) {
                        newjobs[job2.hash] = job2;
                    }
                });
                this.jobs = newjobs;
            }
        },

        next: function () {
            if (Object.keys(this.jobs).length > 0) {
                var lowestHash = Object.keys(this.jobs)[0];
                if (this.jobs[lowestHash]) {
                    return this.jobs[lowestHash].execute(this);
                }
            }
            return new Promise((r,e) => r(null));
        },

        run: function() {            
            if (this.isRunning) {
                return;
            }

            if (Object.keys(this.jobs).length > 0) {
                this.isRunning = true;
                this.next().then((job) => {
                    this.remove(job);
                    setTimeout(() => {
                        this.isRunning = false;
                        this.run();
                    }, 1);
                });
            }
        }
    }
}
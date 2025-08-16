import React from "react";

class SecureImage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      imgUrl: null,
      loading: true,
      error: null
    };
    this.objectUrl = null;
  }

  componentDidMount() {
    this.fetchImage();
  }

  componentDidUpdate(prevProps) {
    // re-fetch if fid, hash, or token changes
    if (
      prevProps.fid !== this.props.fid ||
      prevProps.hash !== this.props.hash
    ) {
      this.fetchImage();
    }
  }

  componentWillUnmount() {
    if (this.objectUrl) {
      URL.revokeObjectURL(this.objectUrl);
    }
  }

  async fetchImage() {
    const { fid, hash } = this.props;
    const url = evfranking.api + '/fencers/' + fid + '/photo?hash=' + hash;

    this.setState({ loading: true, error: null });

    try {
      const res = await fetch(url, {
        headers: {
          Authorization: 'Bearer ' + evfranking.key,
        },
        credentials: "same-origin",
        redirect: "manual",
        mode: 'cors'
      });

      if (!res.ok) {
        if (res.status == 403) {
            this.setState({ error: 'No photo available', loading: false });
        }
        else {
            throw new Error(`Fetch failed: ${res.status}`);
        }
      }

      const blob = await res.blob();
      if (this.objectUrl) {
        URL.revokeObjectURL(this.objectUrl);
      }
      this.objectUrl = URL.createObjectURL(blob);

      this.setState({ imgUrl: this.objectUrl, loading: false });
    } catch (err) {
      this.setState({ error: 'Error: ' + err.message, loading: false });
    }
  }

  render() {
    const { imgUrl, loading, error } = this.state;

    if (loading) return <p>Loading…</p>;
    if (error) return <p>{error}</p>;

    return <img src={imgUrl}/>;
  }
}

export default SecureImage;

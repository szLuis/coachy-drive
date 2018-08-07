import React from 'react'
import BreadcrumbsTemplate from '../templates/BreadcrumbsTemplate.rt'

class Breadcrumbs extends React.Component {

    constructor(props) {
        super(props);
    }

    render() {
        return BreadcrumbsTemplate.apply(this)
    }   
}

Breadcrumbs.defaultProps = {
  
}

export default Breadcrumbs;
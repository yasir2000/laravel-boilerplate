package com.hrboilerplate.integration.config;

import org.apache.camel.component.servlet.CamelHttpTransportServlet;
import org.springframework.boot.web.servlet.ServletRegistrationBean;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

/**
 * Apache Camel configuration class for setting up HTTP transport and other Camel-specific beans.
 */
@Configuration
public class CamelConfig {

    /**
     * Registers the Camel HTTP transport servlet to handle HTTP requests for Camel routes.
     */
    @Bean
    public ServletRegistrationBean<CamelHttpTransportServlet> servletRegistrationBean() {
        ServletRegistrationBean<CamelHttpTransportServlet> registration = 
            new ServletRegistrationBean<>(new CamelHttpTransportServlet(), "/camel/*");
        registration.setName("CamelServlet");
        return registration;
    }
}
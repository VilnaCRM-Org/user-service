import { Box } from '@mui/material';
import React from 'react';

import WhyUsCodeIcon from '../../assets/img/why-we-section/code.png';
import WhyUsIntegrationsIcon from '../../assets/img/why-we-section/integrations.png';
import WhyUsMigrationIcon from '../../assets/img/why-we-section/migration.png';
import WhyUsServicesIcon from '../../assets/img/why-we-section/services.png';
import WhyUsSettingsIcon from '../../assets/img/why-we-section/settings.png';
import WhyUsTemplatesIcon from '../../assets/img/why-we-section/templates.png';

import CardList from './CardList/CardList';
import { whyUsStyles } from './styles';
import { WhyUsText } from './WhyUsText';

function WhyUs() {
  const largeCarditemList = [
    {
      id: 'card-item-1',
      imageSrc: WhyUsCodeIcon,
      title: 'why_we.headers.header_open_source',
      text: 'why_we.texts.text_open_source',
    },
    {
      id: 'card-item-2',
      imageSrc: WhyUsSettingsIcon,
      title: 'why_we.headers.header_ease_of_setup',
      text: 'why_we.texts.text_configure_system',
    },
    {
      id: 'card-item-3',
      imageSrc: WhyUsTemplatesIcon,
      title: 'why_we.headers.header_ready_templates',
      text: 'why_we.texts.text_you_have_store',
    },
    {
      id: 'card-item-4',
      imageSrc: WhyUsServicesIcon,
      title: 'why_we.headers.header_ideal_for_services',
      text: 'why_we.texts.text_we_know_specific_needs',
    },
    {
      id: 'card-item-5',
      imageSrc: WhyUsIntegrationsIcon,
      title: 'why_we.headers.header_all_required_integrations',
      text: 'why_we.texts.text_connect_your_cms',
    },
    {
      id: 'card-item-6',
      imageSrc: WhyUsMigrationIcon,
      title: 'why_we.headers.header_bonus',
      text: 'why_we.texts.text_switch_to_vilna',
    },
  ];
  return (
    <Box sx={whyUsStyles.wrapper} id="Advantages" component="section">
      <WhyUsText />
      <CardList largeCarditemList={largeCarditemList} />
    </Box>
  );
}

export default WhyUs;

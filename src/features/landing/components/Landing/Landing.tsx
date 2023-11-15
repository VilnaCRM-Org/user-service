import Layout from '@/features/landing/components/Layout/Layout';
import { REGISTRATION_SECTION_ID } from '@/features/landing/utils/constants/constants';
import { AboutVilnaSection } from '../AboutVilnaSection/AboutVilnaSection/AboutVilnaSection';
import { WhyWeSection } from '@/features/landing/components/WhyWeSection/WhyWeSection/WhyWeSection';
import {
  ForWhoSection,
} from '@/features/landing/components/ForWhoSection/ForWhoSection/ForWhoSection';
import {
  UnlimitedIntegrationsSection
} from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection/UnlimitedIntegrationsSection';

export default function Landing() {
  return <Layout>
    <AboutVilnaSection />
    <WhyWeSection />
    <ForWhoSection />
    <UnlimitedIntegrationsSection />
    <section id={REGISTRATION_SECTION_ID}>
      <h1>Registration Placeholder</h1>
      <p>Registration body Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ab commodi,
        consequuntur dolore earum eos expedita fugit, inventore ipsum nihil non quos rerum sed
        veritatis vero, vitae? Ad beatae natus nisi.</p>
    </section>
  </Layout>;
}

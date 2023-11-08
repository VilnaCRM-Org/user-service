import Layout from '@/features/landing/components/Layout/Layout';
import { REGISTRATION_SECTION_ID } from '@/features/landing/utils/constants/constants';
import { AboutVilnaSection } from '../AboutVilnaSection/AboutVilnaSection/AboutVilnaSection';

export default function Landing() {
  return <Layout>
    <AboutVilnaSection />
    <section id={REGISTRATION_SECTION_ID}>
      <h1>Registration Placeholder</h1>
      <p>Registration body Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ab commodi,
        consequuntur dolore earum eos expedita fugit, inventore ipsum nihil non quos rerum sed
        veritatis vero, vitae? Ad beatae natus nisi.</p>
    </section>
  </Layout>;
}
